<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Command;

use ItkDev\F2ApiClient\Client\ApiClient;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'f2:api:client')]
class F2ApiClientCommand
{
    public function __invoke(
        SymfonyStyle $io,
        #[Argument]
        string $action,
        #[Argument]
        ?string $arg = null,
        #[Option(description: 'Path to to cache directory. If not specified, no requests will be cached.')]
        ?string $cacheDirectory = null,
    ): int
    {
        $client = $this->createClient(cacheDirectory: $cacheDirectory);

        $response = match ($action) {
            'getServiceIndex' => $client->getServiceIndex(),
            'caseSearch' => $client->caseSearch((string)$arg),
            'caseById' => $client->caseById((string)$arg),
            'matterSearch' => $client->matterSearch((string)$arg),
            'matterById' => $client->matterById((string)$arg),
            'matterByMatterNumber' => $client->matterByMatterNumber((string)$arg),
            'documentById'=> $client->documentById((string)$arg),
            default => throw new InvalidArgumentException(sprintf('Invalid action: %s', $action)),
        };

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
}
