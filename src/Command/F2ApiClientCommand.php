<?php

declare(strict_types=1);

namespace ItkDev\F2ApiClient\Command;

use ItkDev\F2ApiClient\Client\ApiClient;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'f2:api:client')]
class F2ApiClientCommand
{
    public function __invoke(SymfonyStyle $io, #[Argument] string $action, #[Argument] ?string $arg = null): int
    {
        $client = $this->createClient();

        $getArray = static function (?string $value): array {
            if (null === $value) {
                throw new InvalidArgumentException('Missing JSON argument');
            }
            try {
                $array = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new InvalidArgumentException(sprintf('Invalid JSON: %s', $e->getMessage()));
            }

            return $array;
        };

        $response = match ($action) {
            'getServiceIndex' => $client->getServiceIndex(),
            'caseSearch' => $client->caseSearch($arg),
            'caseById' => $client->caseById($arg),
            'matterSearch' => $client->matterSearch($arg),
            'matterById' => $client->matterById($arg),
            'matterByMatterNumber' => $client->matterByMatterNumber($arg),
            default => throw new InvalidArgumentException(sprintf('Invalid action: %s', $action)),
        };

        $io->writeln((string) json_encode(
            $response,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        return Command::SUCCESS;
    }

    private function createClient(): ApiClient
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

            'cache_item_pool' => new FilesystemAdapter(),
        ];

        return new ApiClient($config);
    }
}
