<?php

namespace Console\Commands;

use Shift\Config\Env;
use Shift\Console\Cli;
use Shift\Console\CommandInterface;

#[\Shift\Console\Attributes\Command('about', group: 'diagnostics')]
class About implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $composer = $this->composer();

        $cli->table(['Name', 'Value'], [
            ['Framework', $composer['name'] ?? 'ShiftPHP'],
            ['Description', $composer['description'] ?? 'API framework'],
            ['PHP', PHP_VERSION],
            ['Environment', (string) Env::get('APP_ENV', 'local')],
            ['Root', APP_ROOT],
            ['Application', APP_PATH],
            ['CLI', APP_ROOT . '/shift'],
        ]);
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift about';
    }

    public function getDescription(): string
    {
        return 'Show framework and runtime information.';
    }

    private function composer(): array
    {
        $path = APP_ROOT . '/composer.json';

        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }
}
