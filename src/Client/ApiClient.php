<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Client;

use ItkDev\F2ApiClient\Exception\RuntimeException;
use ItkDev\F2ApiClient\Model\CaseFile;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ApiClient
{
    protected const string METHOD_GET = 'GET';
    protected const string METHOD_POST = 'POST';

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
        $path = '/F2Rest/ServiceIndex';
        $response = $this->client()->request(self::METHOD_GET, $path, [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return $response->toArray();
    }

    /**
     * @return CaseFile[]
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function searchCases(array $query): array
    {
        //        $query = [];
        $path = '/F2Rest/search/cases';
        // @todo  /F2Rest/ServiceIndex.json reports
        //
        // "http://cbrain.com/casefile/rel/case-search": {
        //   "href": "/F2Rest/search/cases",
        //   "title": "Case file search"
        // },
        //
        // which refers to the actual search URL: /F2Rest/searches/cases
        $path = '/F2Rest/searches/cases';
        //        $path = $this->getRequestUrl('http://cbrain.com/casefile/rel/case-search');
        $response = $this->request(self::METHOD_GET, $path, [
            'query' => $query,
        ]);

        $items = [];
        $sxe = new \SimpleXMLElement($response->getContent());
        foreach ($sxe->entry as $entry) {
            $items[] = new CaseFile($entry);
        }

        return $items;
    }

    /**
     * @return array{access_token: string, token_type: string}
     */
    protected function getAccessToken(): array
    {
        // @todo Check existing access token is not expired.

        $client = $this->client();
        $response = $client->request(self::METHOD_POST, '/F2Rest/oauth2/token', [
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
        $resolver->setRequired([
            'api_uri',
            'api_username',
            'api_secret',
            'f2_username',
        ]);
    }

    protected function getRequestUrl(string $rel): string
    {
        // @todo Cache this!
        $index = $this->getServiceIndex();

        $url = $index[$rel]['href'] ?? null;
        if (null === $url) {
            throw new RuntimeException(sprintf('Cannot get rel %s', $rel));
        }

        return $url;
    }
}
