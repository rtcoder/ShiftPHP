<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Console\Quality\CheckResult;
use Shift\Console\Quality\QualityChecks;

#[\Shift\Console\Attributes\Command('lint', aliases: ['l'], group: 'diagnostics')]
class Lint implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $this->render((new QualityChecks())->lint(), 'Lint checks passed.', 'Lint checks failed.');
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift lint';
    }

    public function getDescription(): string
    {
        return 'Run PHP syntax and file hygiene checks.';
    }

    /**
     * @param list<CheckResult> $results
     */
    private function render(array $results, string $successMessage, string $failureMessage): void
    {
        $cli = new Cli();
        $cli->table(['Check', 'Status', 'Details'], array_map(
            static fn (CheckResult $result): array => $result->toRow(),
            $results
        ));

        $failed = array_values(array_filter($results, static fn (CheckResult $result): bool => !$result->passed()));

        if ($failed === []) {
            $cli->success($successMessage);
            return;
        }

        $cli->error($failureMessage);
        exit(1);
    }
}
