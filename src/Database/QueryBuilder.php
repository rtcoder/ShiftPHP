<?php

namespace Shift\Database;

final class QueryBuilder
{
    /** @var list<string> */
    private array $columns = ['*'];

    /** @var list<array{boolean: string, column: string, operator: string, value: mixed}> */
    private array $wheres = [];

    /** @var list<array{column: string, direction: string}> */
    private array $orders = [];

    private ?int $limit = null;

    private ?int $offset = null;

    public function __construct(
        private readonly Database $database,
        private readonly string $table
    ) {
    }

    public function select(string ...$columns): self
    {
        $this->columns = $columns === [] ? ['*'] : $columns;

        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            $operator = strtolower((string) $operatorOrValue);
        }

        $this->wheres[] = [
            'boolean' => 'and',
            'column' => $column,
            'operator' => $this->normalizeOperator($operator),
            'value' => $value,
        ];

        return $this;
    }

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $operator = '=';
            $value = $operatorOrValue;
        } else {
            $operator = strtolower((string) $operatorOrValue);
        }

        $this->wheres[] = [
            'boolean' => 'or',
            'column' => $column,
            'operator' => $this->normalizeOperator($operator),
            'value' => $value,
        ];

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        $this->orders[] = [
            'column' => $column,
            'direction' => $direction,
        ];

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        $this->limit = max(0, $limit);
        $this->offset = $offset === null ? null : max(0, $offset);

        return $this;
    }

    public function get(): array
    {
        return $this->database->query($this->toSql(), $this->bindings())->all();
    }

    public function first(): ?array
    {
        $this->limit(1);

        return $this->database->query($this->toSql(), $this->bindings())->first();
    }

    public function value(string|int $column = 0): mixed
    {
        $this->limit(1);

        return $this->database->query($this->toSql(), $this->bindings())->value($column);
    }

    public function insert(array $values): int
    {
        if ($values === []) {
            throw new DatabaseException('Insert values cannot be empty.');
        }

        $columns = array_keys($values);
        $placeholders = array_fill(0, count($columns), '?');
        $sql = sprintf(
            'insert into %s (%s) values (%s)',
            $this->identifier($this->table),
            implode(', ', array_map(fn (string $column): string => $this->identifier($column), $columns)),
            implode(', ', $placeholders)
        );

        return $this->database->execute($sql, array_values($values));
    }

    public function insertGetId(array $values): string
    {
        $this->insert($values);

        return $this->database->lastInsertId();
    }

    public function update(array $values): int
    {
        if ($values === []) {
            return 0;
        }

        $assignments = array_map(
            fn (string $column): string => $this->identifier($column) . ' = ?',
            array_keys($values)
        );

        $sql = sprintf(
            'update %s set %s%s',
            $this->identifier($this->table),
            implode(', ', $assignments),
            $this->whereSql()
        );

        return $this->database->execute($sql, array_merge(array_values($values), $this->bindings()));
    }

    public function delete(): int
    {
        $sql = sprintf(
            'delete from %s%s',
            $this->identifier($this->table),
            $this->whereSql()
        );

        return $this->database->execute($sql, $this->bindings());
    }

    public function toSql(): string
    {
        $sql = sprintf(
            'select %s from %s%s%s',
            implode(', ', array_map(fn (string $column): string => $this->identifier($column), $this->columns)),
            $this->identifier($this->table),
            $this->whereSql(),
            $this->orderSql()
        );

        if ($this->limit !== null) {
            $sql .= ' limit ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' offset ' . $this->offset;
        }

        return $sql;
    }

    private function whereSql(): string
    {
        if ($this->wheres === []) {
            return '';
        }

        $parts = [];
        foreach ($this->wheres as $index => $where) {
            $prefix = $index === 0 ? ' where ' : ' ' . $where['boolean'] . ' ';
            $parts[] = $prefix . $this->identifier($where['column']) . ' ' . $where['operator'] . ' ?';
        }

        return implode('', $parts);
    }

    private function orderSql(): string
    {
        if ($this->orders === []) {
            return '';
        }

        $parts = array_map(
            fn (array $order): string => $this->identifier($order['column']) . ' ' . $order['direction'],
            $this->orders
        );

        return ' order by ' . implode(', ', $parts);
    }

    private function bindings(): array
    {
        return array_map(
            static fn (array $where): mixed => $where['value'],
            $this->wheres
        );
    }

    private function identifier(string $identifier): string
    {
        if ($identifier === '*') {
            return $identifier;
        }

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)*$/', $identifier)) {
            throw new DatabaseException("Invalid database identifier '{$identifier}'.");
        }

        return $identifier;
    }

    private function normalizeOperator(string $operator): string
    {
        $allowed = ['=', '!=', '<>', '>', '>=', '<', '<=', 'like', 'not like'];

        if (!in_array($operator, $allowed, true)) {
            throw new DatabaseException("Invalid query operator '{$operator}'.");
        }

        return $operator;
    }
}
