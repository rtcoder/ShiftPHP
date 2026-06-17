<?php

namespace Shift\Database;

use Shift\Config\Env;

final class DatabaseConfig
{
    public function __construct(
        public readonly string $driver,
        public readonly string $database,
        public readonly ?string $host = null,
        public readonly ?int $port = null,
        public readonly ?string $username = null,
        public readonly ?string $password = null,
        public readonly string $charset = 'utf8mb4',
        public readonly ?string $dsn = null,
        public readonly array $options = []
    ) {
    }

    public static function fromEnv(string $prefix = 'DB_'): self
    {
        $driver = strtolower((string) Env::get($prefix . 'CONNECTION', Env::get($prefix . 'DRIVER', 'mysql')));

        return new self(
            driver: $driver,
            database: (string) Env::get($prefix . 'DATABASE', ''),
            host: Env::get($prefix . 'HOST', '127.0.0.1'),
            port: self::intOrNull(Env::get($prefix . 'PORT')),
            username: Env::get($prefix . 'USERNAME', Env::get($prefix . 'USER')),
            password: Env::get($prefix . 'PASSWORD', Env::get($prefix . 'PASS')),
            charset: (string) Env::get($prefix . 'CHARSET', 'utf8mb4'),
            dsn: Env::get($prefix . 'DSN')
        );
    }

    public function dsn(): string
    {
        if ($this->dsn !== null && $this->dsn !== '') {
            return $this->dsn;
        }

        return match ($this->driver) {
            'sqlite' => 'sqlite:' . ($this->database !== '' ? $this->database : ':memory:'),
            'pgsql' => $this->pgsqlDsn(),
            'sqlsrv' => $this->sqlsrvDsn(),
            default => $this->mysqlDsn(),
        };
    }

    private static function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function mysqlDsn(): string
    {
        $parts = [
            'host=' . ($this->host ?? '127.0.0.1'),
            'dbname=' . $this->database,
            'charset=' . $this->charset,
        ];

        if ($this->port !== null) {
            $parts[] = 'port=' . $this->port;
        }

        return 'mysql:' . implode(';', $parts);
    }

    private function pgsqlDsn(): string
    {
        $parts = [
            'host=' . ($this->host ?? '127.0.0.1'),
            'dbname=' . $this->database,
        ];

        if ($this->port !== null) {
            $parts[] = 'port=' . $this->port;
        }

        return 'pgsql:' . implode(';', $parts);
    }

    private function sqlsrvDsn(): string
    {
        $server = $this->host ?? '127.0.0.1';

        if ($this->port !== null) {
            $server .= ',' . $this->port;
        }

        return 'sqlsrv:Server=' . $server . ';Database=' . $this->database;
    }
}
