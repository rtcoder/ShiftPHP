<?php

namespace Shift\Database;

use PDO;
use PDOStatement;

final class QueryResult
{
    public function __construct(private readonly PDOStatement $statement)
    {
    }

    public function all(): array
    {
        return $this->statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function first(): ?array
    {
        $row = $this->statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function value(string|int $column = 0): mixed
    {
        $row = $this->statement->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        if (is_int($column)) {
            return array_values($row)[$column] ?? null;
        }

        return $row[$column] ?? null;
    }

    public function affectedRows(): int
    {
        return $this->statement->rowCount();
    }

    public function statement(): PDOStatement
    {
        return $this->statement;
    }
}
