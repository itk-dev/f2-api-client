<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Client;

use ItkDev\F2ApiClient\Exception\ApiException;
use ItkDev\F2ApiClient\Exception\RuntimeException;
use ItkDev\F2ApiClient\Model\Atom;
use ItkDev\F2ApiClient\Model\CaseFile;
use ItkDev\F2ApiClient\Model\Matter;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    private readonly array $options;
    private ?HttpClientInterface $client = null;

    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options = $resolver->resolve($options);
    }

    /**
     * @return array<string, array{href: string, title: string}>
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getServiceIndex(): array
    {
        $cache = new ProxyAdapter(pool: $this->options['cache_item_pool']);
        $cacheKey = sha1(__METHOD__);

        $result = $cache->get($cacheKey, function (CacheItemInterface $item): array {
            $item->expiresAfter($this->options['cache_item_lifetime']);

            $path = '/F2Rest/ServiceIndex';
            $response = $this->client()->request(Request::METHOD_GET, $path, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            return $response->toArray();
        });

        return $result;
    }

    /**
     * @return Atom[]
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function caseSearch(string $searchTerms, int $count = 10): array
    {
        $query = [
            'searchTerms' => $searchTerms,
            'count' => $count,
        ];

        $url = $this->getRequestUrl('http://cbrain.com/casefile/rel/case-search', $query, isSearch: true);
        $response = $this->request(Request::METHOD_GET, $url);

        $items = [];
        $sxe = new \SimpleXMLElement($response->getContent());
        foreach ($sxe->entry as $entry) {
            $items[] = Atom::fromSimpleXMLElement($entry);
        }

        return $items;
    }

    public function caseById(string $id): CaseFile
    {
        $url = $this->getRequestUrl('http://cbrain.com/casefile/rel/case-by-id', [
            'id' => $id,
        ]);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new ApiException($response);
        }

        return CaseFile::fromSimpleXMLElement(new \SimpleXMLElement($response->getContent()));
    }

    public function matterSearch(string $searchTerms, int $count = 10): array
    {
        $query = [
            'searchTerms' => $searchTerms,
            'count' => $count,
        ];

        $url = $this->getRequestUrl('http://cbrain.com/casefile/rel/matter-search', $query, isSearch: true);
        $response = $this->request(Request::METHOD_GET, $url);

        $items = [];
        $sxe = new \SimpleXMLElement($response->getContent());
        foreach ($sxe->entry as $entry) {
            $items[] = Atom::fromSimpleXMLElement($entry);
        }

        return $items;
    }

    public function matterById(string $id): Matter
    {
        $url = $this->getRequestUrl('http://cbrain.com/casefile/rel/matter-by-id', [
            'id' => $id,
        ]);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new ApiException($response);
        }

        return Matter::fromSimpleXMLElement(new \SimpleXMLElement($response->getContent()));
    }

    public function matterByMatterNumber(string $matterNumber): Matter
    {
        $url = $this->getRequestUrl('http://cbrain.com/casefile/rel/matter-by-matter-number', [
            'matterNumber' => $matterNumber,
        ]);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new ApiException($response);
        }

        return Matter::fromSimpleXMLElement(new \SimpleXMLElement($response->getContent()));
    }

    /**
     * @return array{access_token: string, token_type: string}
     */
    protected function getAccessToken(): array
    {
        // @todo Check existing access token is not expired.

        $client = $this->client();
        $response = $client->request(Request::METHOD_POST, '/F2Rest/oauth2/token', [
            'auth_basic' => [
                $this->options['api_username'],
                $this->options['api_secret'],
            ],
            'headers' => [
                'accept' => 'application/json',
            ],
            'body' => [
                'grant_type' => 'password',
                'username' => $this->options['f2_username'],
            ],
        ]);

        /** @var array{access_token: string, token_type: string} $token */
        $token = $response->toArray();

        // @todo Store access token.

        return $token;
    }

    protected function request(string $method, string $path, array $options = []): ResponseInterface
    {
        $accessToken = $this->getAccessToken();

        return $this->client()->request(
            $method,
            $path,
            $options
            + [
                'auth_bearer' => $accessToken['access_token'],
            ],
        );
    }

    protected function client(): HttpClientInterface
    {
        if (null === $this->client) {
            $this->client = HttpClient::create([
                'base_uri' => $this->options['api_uri'],
            ]);
        }

        return $this->client;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired([
                'api_uri',
                'api_username',
                'api_secret',
                'f2_username',
            ])
            ->setDefault('cache_item_lifetime', 86400)
            ->setAllowedTypes('cache_item_lifetime', 'int')
            ->setRequired('cache_item_pool')
            ->setAllowedTypes('cache_item_pool', CacheItemPoolInterface::class);
    }

    protected function getRequestUrl(string $rel, array $values, bool $isSearch = false): string
    {
        $index = $this->getServiceIndex();

        $url = $index[$rel]['href'] ?? null;
        if (null === $url) {
            throw new RuntimeException(sprintf('Cannot get rel %s', $rel));
        }

        if ($isSearch) {
            try {
                $cache = new ProxyAdapter(pool: $this->options['cache_item_pool']);
                $cacheKey = sha1(__METHOD__ . '|||' . $rel);

                $url = $cache->get($cacheKey, function (CacheItemInterface $item) use ($url) {
                    $item->expiresAfter($this->options['cache_item_lifetime']);

                    $response = $this->request(Request::METHOD_GET, $url);
                    $sxe = new \SimpleXMLElement($response->getContent());

                    $searchUrl = (string) $sxe->Url['template'];
                    if (empty($searchUrl)) {
                        throw new RuntimeException(sprintf('Cannot get search template URL for %s', $url));
                    }

                    return $searchUrl;
                });
            } catch (\Exception $e) {
                throw new RuntimeException(sprintf('Cannot get search URL for rel %s', $rel), previous: $e);
            }
        }

        return $this->replacePlaceholders($url, $values);
    }

    protected function replacePlaceholders(string $url, array $values): string
    {
        // Replace URL placeholders ('{…}')
        return preg_replace_callback(
            '/{(?P<name>[^}]+)}/',
            static function (array $matches) use ($url, $values): string {
                $name = $matches['name'];
                if (!array_key_exists($name, $values)) {
                    throw new RuntimeException(sprintf('Missing value %s for URL %s', $name, $url));
                }

                return rawurlencode((string) $values[$name]);
            },
            $url,
        );
    }
}
