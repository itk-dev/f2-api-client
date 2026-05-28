<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Client;

use ItkDev\F2ApiClient\Exception\ApiException;
use ItkDev\F2ApiClient\Exception\RuntimeException;
use ItkDev\F2ApiClient\Model\AbstractItem;
use ItkDev\F2ApiClient\Model\Atom;
use ItkDev\F2ApiClient\Model\CaseFile;
use ItkDev\F2ApiClient\Model\Document;
use ItkDev\F2ApiClient\Model\Matter;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @phpstan-type AccessToken array{access_token: string, token_type: string, expires_in: int, refresh_token: string}
 */
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
        $cache = $this->getCache();
        $cacheKey = sha1(__METHOD__);

        return $cache->get($cacheKey, function (CacheItemInterface $item): array {
            $item->expiresAfter((int) $this->options['cache_item_lifetime']);

            $path = '/F2Rest/ServiceIndex';
            $response = $this->client()->request(Request::METHOD_GET, $path, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            return $response->toArray();
        });
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

        $url = $this->getSearchRequestUrl('http://cbrain.com/casefile/rel/case-search', $query);
        $response = $this->request(Request::METHOD_GET, $url);

        return $this->createSearchResult($response);
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

        return $this->createItemResult($response, CaseFile::class);
    }

    public function matterSearch(string $searchTerms, int $count = 10): array
    {
        $query = [
            'searchTerms' => $searchTerms,
            'count' => $count,
        ];

        $url = $this->getSearchRequestUrl('http://cbrain.com/casefile/rel/matter-search', $query);
        $response = $this->request(Request::METHOD_GET, $url);

        return $this->createSearchResult($response);
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

        return $this->createItemResult($response, Matter::class);
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

        return $this->createItemResult($response, Matter::class);
    }

    public function documentById(string $id): Document
    {
        $url = $this->getRequestUrl('http://cbrain.com/casefile/rel/document-by-id', [
            'id' => $id,
        ]);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new ApiException($response);
        }

        return $this->createItemResult($response, Document::class);
    }

    /**
     * @return AccessToken
     */
    protected function getAccessToken(): array
    {
        $cache = $this->getCache();
        $cacheKey = sha1(__METHOD__);

        return $cache->get($cacheKey, function (CacheItemInterface $item): array {
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

            /** @var AccessToken */
            $token = $response->toArray();

            $item->expiresAfter((int) $token['expires_in'] - 60);

            return $token;
        });
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
            ->setDefault('cache_item_lifetime', 86_400)
            ->setAllowedTypes('cache_item_lifetime', 'int')
            ->setRequired('cache_item_pool')
            ->setAllowedTypes('cache_item_pool', CacheItemPoolInterface::class);
    }

    protected function getRequestUrl(string $rel, array $values): string
    {
        $index = $this->getServiceIndex();

        $url = $index[$rel]['href'] ?? null;
        if (null === $url) {
            throw new RuntimeException(sprintf('Cannot get rel %s', $rel));
        }

        return $this->replacePlaceholders($url, $values);
    }

    protected function getSearchRequestUrl(string $rel, array $values): string
    {
        $url = $this->getRequestUrl($rel, []);

        try {
            $cache = $this->getCache();
            $cacheKey = sha1(__METHOD__ . '|||' . $rel);

            $url = $cache->get($cacheKey, function (CacheItemInterface $item) use ($url) {
                $item->expiresAfter((int) $this->options['cache_item_lifetime']);

                $response = $this->request(Request::METHOD_GET, $url);
                $sxe = new \SimpleXMLElement($response->getContent());

                // @mago-ignore analysis:mixed-array-access,non-documented-property
                $searchUrl = (string) $sxe->Url['template'];
                if (!filter_var($searchUrl, FILTER_VALIDATE_URL)) {
                    throw new RuntimeException(sprintf('Cannot get search template URL for %s', $url));
                }

                return $searchUrl;
            });
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Cannot get search URL for rel %s', $rel), previous: $e);
        }

        return $this->replacePlaceholders($url, $values);
    }

    protected function replacePlaceholders(string $url, array $values): string
    {
        // Replace URL placeholders ('{…}')
        return (string) preg_replace_callback(
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

    protected function getCache(): CacheInterface
    {
        /** @var CacheItemPoolInterface $pool */
        $pool = $this->options['cache_item_pool'];

        return new ProxyAdapter(pool: $pool);
    }

    /**
     * @return Atom[]
     *
     * @throws \Exception
     */
    protected function createSearchResult(ResponseInterface $response): array
    {
        $items = [];
        $sxe = new \SimpleXMLElement($response->getContent());
        /** @var \SimpleXMLElement $entry */
        // @mago-ignore analysis:non-documented-property
        foreach ($sxe->entry as $entry) {
            $items[] = Atom::fromSimpleXMLElement($entry);
        }

        return $items;
    }

    /**
     * @template T of AbstractItem
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function createItemResult(ResponseInterface $response, string $class): AbstractItem
    {
        return $class::fromSimpleXMLElement(new \SimpleXMLElement($response->getContent()));
    }
}
