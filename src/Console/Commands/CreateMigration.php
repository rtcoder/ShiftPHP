<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Console\Generator\FileGenerator;
use Shift\Console\Generator\NameFormatter;
use Shift\Console\Generator\StubRenderer;

#[\Shift\Console\Attributes\Command('create:migration', group: 'database')]
class CreateMigration implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $cli = new Cli();
        $name = $args[0] ?? null;

        if (!is_string($name) || $name === '') {
            $cli->error($this->getHelp());
            return;
        }

        $directory = APP_ROOT . '/database/migrations';
        $migrationName = str_replace('-', '_', NameFormatter::slug($name));
        $file = $directory . '/' . date('Y_m_d_His') . '_' . $migrationName . '.php';
        $content = (new StubRenderer())->render('migration', [
            'name' => $migrationName,
        ]);

        $files = new FileGenerator();
        $files->writeFile($file, $content);
        $cli->success('Created: ' . $file);
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift create:migration {name}';
    }

    public function getDescription(): string
    {
        return 'Create a database migration file.';
    }
}
