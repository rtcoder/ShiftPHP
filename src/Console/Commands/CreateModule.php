<?php

namespace Console\Commands;

use Shift\Console\CommandInterface;
use Shift\Console\Generator\GeneratesFiles;
use Shift\Console\Generator\NameFormatter;

class CreateModule implements CommandInterface
{
    use GeneratesFiles;

    public function execute(mixed ...$args): void
    {
        $rawName = $args[0] ?? null;

        if (!is_string($rawName) || $rawName === '') {
            $this->cli->error($this->getHelp());
            return;
        }

        $module = NameFormatter::moduleName($rawName);
        $slug = NameFormatter::slug($rawName);
        $path = $this->modulePath($module);

        foreach (['Commands', 'Controllers', 'Services'] as $directory) {
            $this->files->ensureDirectory($path . '/' . $directory);
        }

        $this->writeAndReport($path . '/Module.php', $this->renderStub('module', [
            'module' => $module,
            'slug' => $slug,
        ]));
        $this->writeAndReport($path . '/config.php', $this->renderStub('config', [
            'slug' => $slug,
        ]));
    }

    public function getHelp(): string
    {
        return 'Usage: php shift.php create:module {name}';
    }

    public function getDescription(): string
    {
        return 'Create a new Shift module.';
    }

}
