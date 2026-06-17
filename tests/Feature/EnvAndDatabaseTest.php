<?php

use Shift\App;
use Shift\Config\Env;
use Shift\Config\EnvLoader;
use Shift\Database\Database;
use Shift\Database\DatabaseConfig;

return [
    '.env loader reads simple environment files' => function (): void {
        $path = tempnam(sys_get_temp_dir(), 'shift-env-');
        $key = 'SHIFT_TEST_ENV_' . bin2hex(random_bytes(4));
        $quotedKey = $key . '_QUOTED';

        try {
            file_put_contents($path, "{$key}=plain\n{$quotedKey}=\"quoted value\"\n");

            (new EnvLoader())->load($path);

            assertSameValue('plain', Env::get($key), 'Env loader should expose plain values.');
            assertSameValue('quoted value', Env::get($quotedKey), 'Env loader should unquote quoted values.');
        } finally {
            putenv($key);
            putenv($quotedKey);
            unset($_ENV[$key], $_ENV[$quotedKey], $_SERVER[$key], $_SERVER[$quotedKey]);

            if (is_string($path) && is_file($path)) {
                unlink($path);
            }
        }
    },
    'database config builds dsn from environment' => function (): void {
        $prefix = 'SHIFT_TEST_DB_' . bin2hex(random_bytes(4)) . '_';

        try {
            setTestEnv($prefix . 'CONNECTION', 'MySQL');
            setTestEnv($prefix . 'HOST', 'db.local');
            setTestEnv($prefix . 'PORT', '3307');
            setTestEnv($prefix . 'DATABASE', 'shift_test');
            setTestEnv($prefix . 'USERNAME', 'shift_user');
            setTestEnv($prefix . 'PASSWORD', 'secret');

            $config = DatabaseConfig::fromEnv($prefix);

            assertSameValue('mysql:host=db.local;dbname=shift_test;charset=utf8mb4;port=3307', $config->dsn(), 'MySQL DSN should be built from env.');
            assertSameValue('shift_user', $config->username, 'Username should be read from env.');
            assertSameValue('secret', $config->password, 'Password should be read from env.');
        } finally {
            clearTestEnvPrefix($prefix);
        }
    },
    'database can execute simple parameterized queries' => function (): void {
        $db = new Database(new DatabaseConfig(
            driver: 'sqlite',
            database: ':memory:'
        ));

        $db->execute('create table users (id integer primary key autoincrement, email text not null)');
        $db->execute('insert into users (email) values (:email)', ['email' => 'dev@example.com']);

        $row = $db->query('select id, email from users where email = :email', ['email' => 'dev@example.com'])->first();

        assertSameValue('dev@example.com', $row['email'] ?? null, 'Database query should fetch inserted rows.');
    },
    'app registers database services lazily' => function (): void {
        $app = new App(makeRequest('GET', '/health'));

        assertSameValue(true, $app->getContainer()->has(Database::class), 'App should register database service.');
        assertSameValue(true, $app->getContainer()->has(DatabaseConfig::class), 'App should register database config service.');
        assertSameValue(true, $app->getContainer()->has('db'), 'App should register db alias.');
    },
];

function setTestEnv(string $key, string $value): void
{
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
    putenv($key . '=' . $value);
}

function clearTestEnvPrefix(string $prefix): void
{
    foreach (array_keys($_ENV) as $key) {
        if (str_starts_with($key, $prefix)) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
    }
}
