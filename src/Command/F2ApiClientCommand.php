<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Command;

use ItkDev\F2ApiClient\ApiClient;
use ItkDev\F2ApiClient\Model\AbstractItem;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'f2:api:client')]
class F2ApiClientCommand extends Command
{
    public const string ACTION_GET_SERVICE_INDEX = 'getServiceIndex';
    public const string ACTION_CASE_SEARCH = 'caseSearch';
    public const string ACTION_CASE_BY_ID = 'caseById';
    public const string ACTION_MATTER_SEARCH = 'matterSearch';
    public const string ACTION_MATTER_BY_ID = 'matterById';
    public const string ACTION_MATTER_BY_MATTER_NUMBER = 'matterByMatterNumber';
    public const string ACTION_DOCUMENT_BY_ID = 'documentById';

    private const array ACTIONS = [
        self::ACTION_GET_SERVICE_INDEX,
        self::ACTION_CASE_SEARCH,
        self::ACTION_CASE_BY_ID,
        self::ACTION_MATTER_SEARCH,
        self::ACTION_MATTER_BY_ID,
        self::ACTION_MATTER_BY_MATTER_NUMBER,
        self::ACTION_DOCUMENT_BY_ID,
    ];

    public function getHelp(): string
    {
        return <<<'EOF'
            Call F2 API endpoint:

            %command.name% getServiceIndex
            %command.name% caseSearch <search-terms>
            %command.name% caseById <case-id>
            %command.name% matterSearch <search-terms>
            %command.name% matterById <matter-id>
            %command.name% matterByMatterNumber <matter-number>
            %command.name% documentById <document-id>

            Examples:

            %command.name% caseSearch "2026 - 618"
            %command.name% matterSearch "test"

            EOF;
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument]
        string $action,
        #[Argument]
        ?string $arg = null,
        #[Option(description: 'Show links.')]
        bool $showLinks = false,
        #[Option(
            description: 'Path to to cache directory. If not specified, the default file system cache directory will be used.',
        )]
        ?string $cacheDirectory = null,
    ): int {
        $client = $this->createClient(cacheDirectory: $cacheDirectory);
        $client->setLogger(new ConsoleLogger($io));

        $response = match ($action) {
            'getServiceIndex' => $client->getServiceIndex(),
            'caseSearch' => $client->caseSearch((string) $arg),
            'caseById' => $client->caseById((int) $arg),
            'matterSearch' => $client->matterSearch((string) $arg),
            'matterById' => $client->matterById((int) $arg),
            'matterByMatterNumber' => $client->matterByMatterNumber((string) $arg),
            'documentById' => $client->documentById((int) $arg),
            default => throw new InvalidArgumentException(sprintf('Invalid action: %s; Must be on of %s.', $action, implode(', ', self::ACTIONS))),
        };

        $response = $this->responseToArray($response);
        if (!$showLinks) {
            // @todo Recurse!
            unset($response['links']);
        }

        $io->writeln((string) json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        return Command::SUCCESS;
    }

    private function createClient(?string $cacheDirectory): ApiClient
    {
        $getEnv = static function (string $name): string {
            $value = getenv($name);
            if (false === $value || '' === trim($value)) {
                throw new RuntimeException(sprintf('Cannot read environment variable %s', $name));
            }

            return $value;
        };

        $config = [
            'api_uri' => $getEnv('F2_API_URI'),
            'api_username' => $getEnv('F2_API_USERNAME'),
            'api_secret' => $getEnv('F2_API_SECRET'),
            'f2_username' => $getEnv('F2_F2_USERNAME'),

            'cache_item_pool' => new FilesystemAdapter(directory: $cacheDirectory),
        ];

        return new ApiClient($config);
    }

    /**
     * @param AbstractItem|array<array-key, mixed> $response
     *
     * @return array<array-key, mixed>
     */
    private function responseToArray(array|AbstractItem $response): array
    {
        // @mago-ignore analysis:invalid-type-cast
        return (array) json_decode(
            json_encode($response, flags: JSON_THROW_ON_ERROR),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
