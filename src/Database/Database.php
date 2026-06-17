<?php

namespace Shift\Database;

use PDO;
use PDOException;

final class Database
{
    private ?PDO $pdo = null;

    public function __construct(private readonly DatabaseConfig $config)
    {
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        try {
            $this->pdo = new PDO(
                $this->config->dsn(),
                $this->config->username,
                $this->config->password,
                $this->options()
            );
        } catch (PDOException $exception) {
            throw new DatabaseException('Database connection failed.', 0, $exception);
        }

        return $this->pdo;
    }

    public function query(string $sql, array $parameters = []): QueryResult
    {
        try {
            $statement = $this->pdo()->prepare($sql);

            if ($statement === false) {
                throw new DatabaseException('Database query could not be prepared.');
            }

            $statement->execute($parameters);

            return new QueryResult($statement);
        } catch (PDOException $exception) {
            throw new DatabaseException('Database query failed.', 0, $exception);
        }
    }

    public function execute(string $sql, array $parameters = []): int
    {
        return $this->query($sql, $parameters)->affectedRows();
    }

    public function transaction(callable $callback): mixed
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback($this);
            $pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }

    private function options(): array
    {
        return $this->config->options + [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
}
