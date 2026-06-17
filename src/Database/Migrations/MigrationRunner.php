<?php

namespace Shift\Database\Migrations;

use Shift\Database\Database;
use Shift\Database\DatabaseException;
use Shift\Database\Migration;
use Throwable;

final class MigrationRunner
{
    public function __construct(
        private readonly Database $database,
        private readonly string $path = APP_ROOT . '/database/migrations'
    ) {
    }

    /**
     * @return list<string>
     */
    public function migrate(): array
    {
        $this->ensureRepository();
        $batch = $this->nextBatch();
        $ran = [];

        foreach ($this->pendingFiles() as $file) {
            $name = $this->migrationName($file);
            $migration = $this->loadMigration($file);

            $this->database->transaction(function (Database $db) use ($migration, $name, $batch): void {
                $migration->up($db);
                $db->table('migrations')->insert([
                    'migration' => $name,
                    'batch' => $batch,
                    'migrated_at' => date(DATE_ATOM),
                ]);
            });

            $ran[] = $name;
        }

        return $ran;
    }

    /**
     * @return list<string>
     */
    public function rollback(): array
    {
        $this->ensureRepository();
        $batch = $this->lastBatch();

        if ($batch === null) {
            return [];
        }

        $rolledBack = [];
        $files = $this->filesByName();
        $records = $this->database
            ->table('migrations')
            ->where('batch', $batch)
            ->orderBy('migration', 'desc')
            ->get();

        foreach ($records as $record) {
            $name = (string) $record['migration'];
            $file = $files[$name] ?? null;

            if ($file === null) {
                throw new DatabaseException("Migration file {$name} not found.");
            }

            $migration = $this->loadMigration($file);

            $this->database->transaction(function (Database $db) use ($migration, $name): void {
                $migration->down($db);
                $db->table('migrations')->where('migration', $name)->delete();
            });

            $rolledBack[] = $name;
        }

        return $rolledBack;
    }

    /**
     * @return list<array{name: string, ran: bool, batch: int|null}>
     */
    public function status(): array
    {
        $this->ensureRepository();
        $applied = $this->applied();
        $rows = [];

        foreach ($this->migrationFiles() as $file) {
            $name = $this->migrationName($file);
            $rows[] = [
                'name' => $name,
                'ran' => array_key_exists($name, $applied),
                'batch' => $applied[$name] ?? null,
            ];
        }

        return $rows;
    }

    public function ensureRepository(): void
    {
        $this->database->execute(
            'create table if not exists migrations (
                migration varchar(255) primary key,
                batch integer not null,
                migrated_at varchar(255) not null
            )'
        );
    }

    /**
     * @return list<string>
     */
    private function pendingFiles(): array
    {
        $applied = $this->applied();

        return array_values(array_filter(
            $this->migrationFiles(),
            fn (string $file): bool => !array_key_exists($this->migrationName($file), $applied)
        ));
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        if (!is_dir($this->path)) {
            return [];
        }

        $files = glob(rtrim($this->path, '/') . '/*.php') ?: [];
        sort($files);

        return $files;
    }

    /**
     * @return array<string, int>
     */
    private function applied(): array
    {
        try {
            $rows = $this->database->table('migrations')->select('migration', 'batch')->get();
        } catch (Throwable) {
            return [];
        }

        $applied = [];
        foreach ($rows as $row) {
            $applied[(string) $row['migration']] = (int) $row['batch'];
        }

        return $applied;
    }

    /**
     * @return array<string, string>
     */
    private function filesByName(): array
    {
        $files = [];

        foreach ($this->migrationFiles() as $file) {
            $files[$this->migrationName($file)] = $file;
        }

        return $files;
    }

    private function loadMigration(string $file): Migration
    {
        $migration = require $file;

        if (!$migration instanceof Migration) {
            throw new DatabaseException("Migration {$file} must return an instance of " . Migration::class . '.');
        }

        return $migration;
    }

    private function nextBatch(): int
    {
        $lastBatch = $this->lastBatch();

        return $lastBatch === null ? 1 : $lastBatch + 1;
    }

    private function lastBatch(): ?int
    {
        $batch = $this->database->query('select max(batch) as batch from migrations')->value('batch');

        return $batch === null ? null : (int) $batch;
    }

    private function migrationName(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }
}
