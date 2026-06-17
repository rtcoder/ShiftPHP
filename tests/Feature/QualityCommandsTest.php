<?php

use Console\Commands\Lint;
use Console\Commands\Qa;
use Shift\Console\CommandRegistry;
use Shift\Console\Quality\ProjectFileFinder;
use Shift\Console\Quality\QualityChecks;

return [
    'command registry discovers quality commands' => function (): void {
        $registry = CommandRegistry::default();
        $commands = $registry->all();

        assertSameValue(Lint::class, $commands['lint'] ?? null, 'Registry should expose lint command.');
        assertSameValue(Qa::class, $commands['qa'] ?? null, 'Registry should expose qa command.');
        assertSameValue(Lint::class, $registry->find('l'), 'Registry should resolve lint alias.');
        assertSameValue(Qa::class, $registry->find('quality'), 'Registry should resolve qa alias.');
        assertSameValue(Qa::class, $registry->find('ci'), 'Registry should resolve ci alias.');
    },
    'quality checks pass for valid php file' => function (): void {
        $root = sys_get_temp_dir() . '/shift-quality-' . bin2hex(random_bytes(6));
        mkdir($root, 0775, true);

        try {
            $file = $root . '/Example.php';
            file_put_contents($file, "<?php\n\necho 'ok';\n");

            $checks = new QualityChecks(new ProjectFileFinder([$file]), $root);
            $result = $checks->phpSyntax();

            assertSameValue('ok', $result->status, 'PHP syntax check should pass for valid PHP.');
        } finally {
            removeDirectory($root);
        }
    },
    'quality checks detect file hygiene issues' => function (): void {
        $root = sys_get_temp_dir() . '/shift-quality-' . bin2hex(random_bytes(6));
        mkdir($root, 0775, true);

        try {
            $file = $root . '/README.md';
            file_put_contents($file, "Bad whitespace  \n");

            $checks = new QualityChecks(new ProjectFileFinder([$file]), $root);
            $result = $checks->fileHygiene();

            assertSameValue('fail', $result->status, 'File hygiene should fail on trailing whitespace.');
            assertStringContains('trailing whitespace', $result->details, 'File hygiene should explain the issue.');
        } finally {
            removeDirectory($root);
        }
    },
    'lint command reports syntax and hygiene checks' => function (): void {
        ob_start();
        (new Lint())->execute();
        $output = ob_get_clean();

        assertStringContains('PHP syntax', $output, 'Lint output should include PHP syntax check.');
        assertStringContains('File hygiene', $output, 'Lint output should include file hygiene check.');
        assertStringContains('Lint checks passed.', $output, 'Lint output should include success message.');
    },
];
