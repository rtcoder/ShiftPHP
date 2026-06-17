<?php

use Shift\Database\Database;
use Shift\Database\DatabaseConfig;
use Shift\Database\Migrations\MigrationRunner;

return [
    'migration runner applies pending migrations and records status' => function (): void {
        $root = makeTempMigrationPath();

        try {
            writeTestMigration(
                $root . '/migrations/2026_06_17_120000_create_widgets_table.php',
                "create table widgets (id integer primary key autoincrement, name text not null)",
                'drop table widgets'
            );

            $db = makeMigrationDatabase();
            $runner = new MigrationRunner($db, $root . '/migrations');

            $pendingStatus = $runner->status();
            assertSameValue(false, $pendingStatus[0]['ran'] ?? null, 'Migration should be pending before migrate runs.');

            $ran = $runner->migrate();
            assertSameValue(['2026_06_17_120000_create_widgets_table'], $ran, 'Pending migration should run once.');

            $count = $db->query('select count(*) as count from migrations')->value('count');
            assertSameValue(1, (int) $count, 'Migration repository should store applied migration.');

            $status = $runner->status();
            assertSameValue(true, $status[0]['ran'] ?? null, 'Status should mark applied migrations as ran.');
            assertSameValue(1, $status[0]['batch'] ?? null, 'First migration batch should be 1.');

            $secondRun = $runner->migrate();
            assertSameValue([], $secondRun, 'Already applied migrations should not run again.');
        } finally {
            removeDirectory($root);
        }
    },
    'migration runner rolls back the latest batch' => function (): void {
        $root = makeTempMigrationPath();

        try {
            writeTestMigration(
                $root . '/migrations/2026_06_17_120000_create_widgets_table.php',
                "create table widgets (id integer primary key autoincrement, name text not null)",
                'drop table widgets'
            );

            $db = makeMigrationDatabase();
            $runner = new MigrationRunner($db, $root . '/migrations');
            $runner->migrate();

            $rolledBack = $runner->rollback();
            assertSameValue(['2026_06_17_120000_create_widgets_table'], $rolledBack, 'Rollback should undo the last batch.');

            $stored = $db->query('select count(*) as count from migrations')->value('count');
            assertSameValue(0, (int) $stored, 'Rollback should remove migration repository records.');

            $missingTable = false;
            try {
                $db->query('select count(*) as count from widgets')->value('count');
            } catch (Throwable) {
                $missingTable = true;
            }

            assertSameValue(true, $missingTable, 'Rollback should call the migration down method.');
        } finally {
            removeDirectory($root);
        }
    },
];

function makeMigrationDatabase(): Database
{
    return new Database(new DatabaseConfig(
        driver: 'sqlite',
        database: ':memory:'
    ));
}

function makeTempMigrationPath(): string
{
    $root = sys_get_temp_dir() . '/shift-migrations-' . bin2hex(random_bytes(6));
    mkdir($root . '/migrations', 0775, true);

    return $root;
}

function writeTestMigration(string $path, string $upSql, string $downSql): void
{
    file_put_contents($path, <<<PHP
<?php

use Shift\\Database\\Database;
use Shift\\Database\\Migration;

return new class extends Migration
{
    public function up(Database \$db): void
    {
        \$db->execute('{$upSql}');
    }

    public function down(Database \$db): void
    {
        \$db->execute('{$downSql}');
    }
};
PHP);
}
