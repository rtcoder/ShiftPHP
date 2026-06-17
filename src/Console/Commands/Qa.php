<?php

namespace Console\Commands;

use Shift\Console\Cli;
use Shift\Console\CommandInterface;
use Shift\Console\Quality\CheckResult;
use Shift\Console\Quality\QualityChecks;

#[\Shift\Console\Attributes\Command('qa', aliases: ['quality', 'ci'], group: 'diagnostics')]
class Qa implements CommandInterface
{
    public function execute(mixed ...$args): void
    {
        $this->render((new QualityChecks())->qa(), 'Quality checks passed.', 'Quality checks failed.');
    }

    public function getHelp(): string
    {
        return 'Usage: ./shift qa';
    }

    public function getDescription(): string
    {
        return 'Run Composer validation, lint, tests, and route checks.';
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
