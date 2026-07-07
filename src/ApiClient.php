<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient;

use ItkDev\F2ApiClient\Exception\ApiException;
use ItkDev\F2ApiClient\Exception\RuntimeException;
use ItkDev\F2ApiClient\Model\AbstractF2Item;
use ItkDev\F2ApiClient\Model\AbstractItem;
use ItkDev\F2ApiClient\Model\Atom;
use ItkDev\F2ApiClient\Model\CaseFile;
use ItkDev\F2ApiClient\Model\Collection;
use ItkDev\F2ApiClient\Model\Document;
use ItkDev\F2ApiClient\Model\Matter;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerTrait;
use Swaggest\JsonDiff\JsonDiff;
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
 * @phpstan-type ServiceIndex array<string, array{href: string, title: string}>
 * @phpstan-type OptionsInput array{
 *   api_uri: string,
 *   api_username: string,
 *   api_secret: string,
 *   f2_username: string,
 *   cache_item_pool: CacheItemPoolInterface,
 *   cache_item_lifetime?: int,
 * }
 * @phpstan-type OptionsResolved array{
 *    api_uri: non-empty-string,
 *    api_username: non-empty-string,
 *    api_secret: non-empty-string,
 *    f2_username: non-empty-string,
 *    cache_item_pool: CacheItemPoolInterface,
 *    cache_item_lifetime: int,
 *  }*/
class ApiClient
{
    use LoggerAwareTrait;
    use LoggerTrait;

    private const string REL_CASE_BY_ID = 'http://cbrain.com/casefile/rel/case-by-id';
    private const string REL_CASE_SEARCH = 'http://cbrain.com/casefile/rel/case-search';
    private const string REL_CONTENT = 'http://cbrain.com/casefile/rel/content';
    private const string REL_CREATE_CASE = 'http://cbrain.com/casefile/rel/create-case';
    private const string REL_CREATE_DOCUMENT = 'http://cbrain.com/casefile/rel/create-document';
    private const string REL_CREATE_MATTER = 'http://cbrain.com/casefile/rel/create-matter';
    private const string REL_DOCUMENT_BY_ID = 'http://cbrain.com/casefile/rel/document-by-id';
    private const string REL_MATTER_BY_ID = 'http://cbrain.com/casefile/rel/matter-by-id';
    private const string REL_MATTER_BY_MATTER_NUMBER = 'http://cbrain.com/casefile/rel/matter-by-matter-number';
    private const string REL_MATTER_SEARCH = 'http://cbrain.com/casefile/rel/matter-search';

    /** @var OptionsResolved */
    private readonly array $options;

    private ?HttpClientInterface $client = null;

    /**
     * @param OptionsInput $options
     */
    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        /** @var OptionsResolved $options */
        $options = $resolver->resolve($options);
        $this->options = $options;
    }

    /**
     * @return ServiceIndex
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

        // @mago-ignore analysis:less-specific-return-statement
        return $cache->get($cacheKey, function (CacheItemInterface $item): array {
            $item->expiresAfter((int) $this->options['cache_item_lifetime']);

            $path = '/F2Rest/ServiceIndex';
            $response = $this->client()->request(Request::METHOD_GET, $path, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            /* @var ServiceIndex */
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

        $url = $this->getSearchRequestUrl(self::REL_CASE_SEARCH, $query);
        $response = $this->request(Request::METHOD_GET, $url);

        return $this->createSearchResult($response);
    }

    public function caseById(int $id): CaseFile
    {
        $url = $this->getRequestUrl(self::REL_CASE_BY_ID, [
            'id' => $id,
        ]);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw $this->createApiException($response);
        }

        return $this->createItemResult($response, CaseFile::class);
    }

    public function caseCreate(array $caseData): CaseFile
    {
        $linkName = self::REL_CREATE_CASE;
        $url = $this->getRequestUrl($linkName);

        // The documentation is unclear on this POE stuff. Does it return 303 or 201?
        // resources/f2-rest-docs/f2-rest-docs-v13s.html#11
        $response = $this->request(Request::METHOD_POST, $url);
        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            $message = 'Cannot get case create POE URLs';
            throw $this->createRuntimeException($message, response: $response);
        }
        try {
            $location = $this->getHeader('location', $response);
        } catch (\Exception $e) {
            $message = 'Cannot get case create POE URLs';
            throw $this->createRuntimeException($message, previous: $e);
        }

        $response = $this->request(Request::METHOD_POST, $location, [
            'json' => $caseData,
        ]);

        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            // @TODO Log stuff.
            $message = 'Cannot create case';
            throw $this->createRuntimeException($message, response: $response);
        }

        return $this->createItemResult($response, CaseFile::class);
    }

    public function caseFileUpdate(CaseFile $caseFile, CaseFile $updatedCaseFile): bool
    {
        $url = $caseFile->links->getUrl('self');
        $diff = new JsonDiff(
            $caseFile->jsonSerialize(),
            $updatedCaseFile->jsonSerialize(),
            options: JsonDiff::SKIP_TEST_OPS,
        );
        $response = $this->request(Request::METHOD_PATCH, $url, [
            'json' => $diff->getPatch()->jsonSerialize(),
        ]);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            $message = sprintf('Cannot update case %s', $caseFile);
            throw $this->createRuntimeException($message, response: $response);
        }

        return true;
    }

    /**
     * @return Atom[]
     */
    public function matterSearch(string $searchTerms, int $count = 10): array
    {
        $query = [
            'searchTerms' => $searchTerms,
            'count' => $count,
        ];

        $url = $this->getSearchRequestUrl(self::REL_MATTER_SEARCH, $query);
        $response = $this->request(Request::METHOD_GET, $url);

        return $this->createSearchResult($response);
    }

    public function matterById(int $id): Matter
    {
        $url = $this->getRequestUrl(self::REL_MATTER_BY_ID, [
            'id' => $id,
        ]);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw $this->createApiException($response);
        }

        return $this->createItemResult($response, Matter::class);
    }

    public function matterByMatterNumber(string $matterNumber): Matter
    {
        $url = $this->getRequestUrl(self::REL_MATTER_BY_MATTER_NUMBER, [
            'matterNumber' => $matterNumber,
        ]);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw $this->createApiException($response);
        }

        return $this->createItemResult($response, Matter::class);
    }

    public function matterCreate(array $matterData, ?CaseFile $case = null): Matter
    {
        $linkName = self::REL_CREATE_MATTER;
        $url = null !== $case ? $case->links->getUrl($linkName) : $this->getRequestUrl($linkName);

        // The documentation is unclear on this POE stuff. Does it return 303 or 201?
        // resources/f2-rest-docs/f2-rest-docs-v13s.html#11
        $response = $this->request(Request::METHOD_POST, $url);
        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            $message = null !== $case
                ? sprintf('Cannot get matter create POE URL for case %s', $case)
                : 'Cannot get matter create POE URLs';
            throw $this->createRuntimeException($message, response: $response);
        }
        try {
            $location = $this->getHeader('location', $response);
        } catch (\Exception $e) {
            $message = null !== $case
                ? sprintf('Cannot get matter create POE URL for case %s', $case)
                : 'Cannot get matter create POE URLs';
            throw $this->createRuntimeException($message, previous: $e);
        }

        $response = $this->request(Request::METHOD_POST, $location, [
            'json' => $matterData,
        ]);

        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            $message = null !== $case
                ? sprintf('Cannot create matter for case %s', $case)
                : 'Cannot create matter';
            throw $this->createRuntimeException($message, response: $response);
        }

        return $this->createItemResult($response, Matter::class);
    }

    public function matterUpdate(Matter $matter, Matter $updatedMatter): bool
    {
        $url = $matter->links->getUrl('self');
        $diff = $this->computeDiff($matter, $updatedMatter);
        if ($diff->getDiffCnt() > 0) {
            $response = $this->request(Request::METHOD_PATCH, $url, [
                'json' => $diff->getPatch()->jsonSerialize(),
            ]);

            if (Response::HTTP_OK !== $response->getStatusCode()) {
                $message = sprintf('Cannot update matter %s', $matter);
                throw $this->createRuntimeException($message, response: $response);
            }
        }

        return true;
    }

    public function documentById(int $id): Document
    {
        $url = $this->getRequestUrl(self::REL_DOCUMENT_BY_ID, [
            'id' => $id,
        ]);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw $this->createApiException($response);
        }

        return $this->createItemResult($response, Document::class);
    }

    public function documentCreate(array $documentData, string $filename, Matter $matter): Document
    {
        $linkName = self::REL_CREATE_DOCUMENT;
        $url = $matter->links->getUrl($linkName);

        // The documentation is unclear on this POE stuff. Does it return 303 or 201?
        // resources/f2-rest-docs/f2-rest-docs-v13s.html#11
        $response = $this->request(Request::METHOD_POST, $url);
        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            $message = sprintf('Cannot get document create POE URL for matter %s', $matter);
            throw $this->createRuntimeException($message, response: $response);
        }
        try {
            $location = $this->getHeader('location', $response);
        } catch (\Exception $e) {
            $message = sprintf('Cannot get document create POE URL for matter %s', $matter);
            throw $this->createRuntimeException($message, previous: $e);
        }

        $fileHandle = fopen($filename, 'r');
        if (false === $fileHandle) {
            $message = sprintf('Cannot open document file %s for matter %s', $filename, $matter);
            throw $this->createRuntimeException($message);
        }

        try {
            $response = $this->request(Request::METHOD_POST, $location, [
                'body' => $documentData
                    + [
                        'File' => $fileHandle,
                    ],
            ]);
        } finally {
            fclose($fileHandle);
        }

        if (Response::HTTP_CREATED !== $response->getStatusCode()) {
            $message = sprintf('Cannot create document for matter %s', $matter);
            throw $this->createRuntimeException($message, response: $response);
        }

        return $this->createItemResult($response, Document::class);
    }

    public function documentUpdate(Document $document, Document $updatedDocument, ?string $filename = null): bool
    {
        $url = $document->links->getUrl('self');
        $diff = $this->computeDiff($document, $updatedDocument);
        if ($diff->getDiffCnt() > 0) {
            $response = $this->request(Request::METHOD_PATCH, $url, [
                'json' => $diff->getPatch()->jsonSerialize(),
            ]);

            if (Response::HTTP_OK !== $response->getStatusCode()) {
                $message = sprintf('Cannot update document %s', $document);
                throw $this->createRuntimeException($message, response: $response);
            }
        }

        if (null !== $filename) {
            $fileHandle = fopen($filename, 'r');
            if (false === $fileHandle) {
                $message = sprintf('Cannot open document file %s', $filename);
                throw $this->createRuntimeException($message);
            }

            $url = $document->links->getUrl(self::REL_CONTENT);

            try {
                $response = $this->request(Request::METHOD_PUT, $url, [
                    'body' => [
                        'File' => $fileHandle,
                    ],
                ]);
            } finally {
                fclose($fileHandle);
            }

            if (Response::HTTP_NO_CONTENT !== $response->getStatusCode()) {
                $message = sprintf('Cannot update file on document %s', $document);
                throw $this->createRuntimeException($message, response: $response);
            }
        }

        return true;
    }

    public function getDocumentContent(Document $document): ResponseInterface
    {
        $url = $document->links->getUrl(self::REL_CONTENT);
        $response = $this->request(Request::METHOD_GET, $url);

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw $this->createApiException($response);
        }

        return $response;
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
        if (Request::METHOD_PATCH === $method && array_key_exists('json', $options)) {
            if (!array_key_exists('headers', $options) || !is_array($options['headers'])) {
                $options['headers'] = [];
            }
            $options['headers']['content-type'] = 'application/json-patch';
        }

        $accessToken = $this->getAccessToken();

        try {
            $response = $this->client()->request(
                $method,
                $path,
                $options
                + [
                    'auth_bearer' => $accessToken['access_token'],
                ],
            );
        } catch (\Exception $exception) {
            $this->error('Request error: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            throw $exception;
        }

        $this->debug('request: {data}', [
            'data' => json_encode([
                'method' => $method,
                'path' => $path,
                'options' => $options,
                'response' => [
                    'status_code' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(false),
                    'content' => $response->getContent(false),
                ],
            ], JSON_PRETTY_PRINT),
        ]);

        return $response;
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
            ->setRequired('cache_item_pool')
            ->setAllowedTypes('cache_item_pool', CacheItemPoolInterface::class)
            ->setDefault('cache_item_lifetime', 86_400)
            ->setAllowedTypes('cache_item_lifetime', 'int');
    }

    protected function getRequestUrl(string $rel, array $values = []): string
    {
        $index = $this->getServiceIndex();

        $url = $index[$rel]['href'] ?? null;
        if (null === $url) {
            throw $this->createRuntimeException(sprintf('Cannot get rel %s', $rel));
        }

        return $this->replacePlaceholders($url, $values);
    }

    protected function getSearchRequestUrl(string $rel, array $values): string
    {
        $url = $this->getRequestUrl($rel, []);

        try {
            $cache = $this->getCache();
            $cacheKey = sha1(__METHOD__.'|||'.$rel);

            $url = $cache->get($cacheKey, function (CacheItemInterface $item) use ($url) {
                $item->expiresAfter((int) $this->options['cache_item_lifetime']);

                $response = $this->request(Request::METHOD_GET, $url);
                $sxe = new \SimpleXMLElement($response->getContent());

                // @mago-ignore analysis:mixed-array-access,non-documented-property
                $searchUrl = (string) $sxe->Url['template'];
                if (!filter_var($searchUrl, FILTER_VALIDATE_URL)) {
                    throw $this->createRuntimeException(sprintf('Cannot get search template URL for %s', $url));
                }

                return $searchUrl;
            });
        } catch (\Exception $e) {
            throw $this->createRuntimeException(sprintf('Cannot get search URL for rel %s', $rel), previous: $e);
        }

        return $this->replacePlaceholders($url, $values);
    }

    protected function replacePlaceholders(string $url, array $values): string
    {
        // Replace URL placeholders ('{…}')
        return (string) preg_replace_callback(
            '/{(?P<name>[^}]+)}/',
            function (array $matches) use ($url, $values): string {
                $name = $matches['name'];
                if (!array_key_exists($name, $values)) {
                    throw $this->createRuntimeException(sprintf('Missing value %s for URL %s', $name, $url));
                }

                return rawurlencode((string) $values[$name]);
            },
            $url,
        );
    }

    protected function getCache(): CacheInterface
    {
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
        // @mago-ignore analysis:non-documented-property
        /** @var \SimpleXMLElement[] $elements */
        $elements = $sxe->entry;
        foreach ($elements as $element) {
            $items[] = Atom::fromSimpleXMLElement($element);
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
        $item = new \SimpleXMLElement($response->getContent());

        // @mago-ignore analysis:invalid-return-statement
        return $class::fromSimpleXMLElement($item);
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (null !== $this->logger) {
            $message = '[F2 API client] '.(string) $message;
            $this->logger->log($level, $message, $context);
        }
    }

    /**
     * Get header value.
     */
    private function getHeader(string $name, ResponseInterface $response): string
    {
        $headers = $response->getHeaders();
        $name = strtolower($name);
        if (!array_key_exists($name, $headers) || 0 === count($headers[$name])) {
            throw $this->createRuntimeException(sprintf('Header "%s" not found.', $name));
        }

        return reset($headers[$name]);
    }

    private function createApiException(ResponseInterface $response): ApiException
    {
        $exception = new ApiException($response);

        return $this->logException($exception);
    }

    private function createRuntimeException(
        string $message,
        ?ResponseInterface $response = null,
        ?\Exception $previous = null,
    ): RuntimeException {
        $apiException = null !== $response ? new ApiException($response) : null;

        // @todo Handle the case when both previous and apiException are not null.
        $exception = new RuntimeException($message, previous: $apiException ?? $previous);

        return $this->logException($exception);
    }

    /**
     * @template T of RuntimeException
     *
     * @param T $exception
     *
     * @return T
     */
    private function logException(RuntimeException $exception): RuntimeException
    {
        $this->error('exception: {message}', [
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);

        return $exception;
    }

    public function getLinkCollection(AbstractF2Item $item, string $rel): Collection
    {
        $url = $item->links->getUrl($rel);
        $response = $this->request(Request::METHOD_GET, $url);

        $sxe = new \SimpleXMLElement($response->getContent());

        return Collection::fromSimpleXMLElement($sxe);
    }

    private function computeDiff(AbstractF2Item $old, AbstractF2Item $new): JsonDiff
    {
        $oldValues = $old::filterForJsonPatch($old->jsonSerialize());
        $newValues = $new::filterForJsonPatch($new->jsonSerialize());

        return new JsonDiff($oldValues, $newValues, options: JsonDiff::SKIP_TEST_OPS);
    }
}
