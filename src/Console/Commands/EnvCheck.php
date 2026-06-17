<?php

namespace Console\Commands;

use Shift\Config\Env;
use Shift\Console\Cli;
use Shift\Console\CommandInterface;

#[\Shift\Console\Attributes\Command('env:check', group: 'diagnostics')]
class EnvCheck implements CommandInterface
{
    /** @var list<string> */
    private array $required = [
        'APP_ENV',
        'DB_CONNECTION',
        'DB_DATABASE',
    ];

    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $rows = [];
        $missing = [];

        foreach ($this->required as $key) {
            $value = Env::get($key);
            $ok = $value !== null && $value !== '';

            if (!$ok) {
                $missing[] = $key;
            }

            $rows[] = [
                $key,
                $ok ? 'ok' : 'missing',
                $this->mask($key, $value),
            ];
        }

        $envFile = is_file(APP_ROOT . '/.env') ? 'yes' : 'no';
        $cli->debug('.env file: ' . $envFile);
        $cli->table(['Variable', 'Status', 'Value'], $rows);

        if ($missing === []) {
            $cli->success('Environment configuration OK.');
            return;
        }

        $cli->warning('Missing required environment variables: ' . implode(', ', $missing));
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift env:check';
    }

    public function getDescription(): string
    {
        return 'Validate required environment variables.';
    }

    private function mask(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (str_contains($key, 'PASSWORD') || str_contains($key, 'SECRET') || str_contains($key, 'TOKEN')) {
            return '***';
        }

        return (string) $value;
    }
}
