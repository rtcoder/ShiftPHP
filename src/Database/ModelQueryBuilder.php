<?php

namespace Shift\Database;

/**
 * @template TModel of Model
 */
final class ModelQueryBuilder
{
    private QueryBuilder $query;

    /**
     * @param class-string<TModel> $modelClass
     */
    public function __construct(
        private readonly string $modelClass,
        private readonly Database $database
    ) {
        $this->query = $database->table($modelClass::table());
    }

    public function select(string ...$columns): self
    {
        $this->query->select(...$columns);

        return $this;
    }

    public function where(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        func_num_args() === 2
            ? $this->query->where($column, $operatorOrValue)
            : $this->query->where($column, $operatorOrValue, $value);

        return $this;
    }

    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): self
    {
        func_num_args() === 2
            ? $this->query->orWhere($column, $operatorOrValue)
            : $this->query->orWhere($column, $operatorOrValue, $value);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        $this->query->limit($limit, $offset);

        return $this;
    }

    /**
     * @return list<TModel>
     */
    public function get(): array
    {
        $modelClass = $this->modelClass;

        return array_map(
            fn (array $row): Model => $modelClass::hydrate($row),
            $this->query->get()
        );
    }

    /**
     * @return TModel|null
     */
    public function first(): ?Model
    {
        $modelClass = $this->modelClass;
        $row = $this->query->first();

        return $row === null ? null : $modelClass::hydrate($row);
    }

    /**
     * @return TModel|null
     */
    public function find(mixed $id): ?Model
    {
        $modelClass = $this->modelClass;

        return $this->where($modelClass::primaryKey(), $id)->first();
    }

    /**
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        $modelClass = $this->modelClass;
        $model = new $modelClass();
        $model->fill($attributes);
        $values = $model->storableAttributes();
        $primaryKey = $modelClass::primaryKey();

        if (($values[$primaryKey] ?? null) === null) {
            unset($values[$primaryKey]);
        }

        $id = $this->database->table($modelClass::table())->insertGetId($values);

        if (array_key_exists($primaryKey, $modelClass::columns())) {
            $model->{$primaryKey} = $model::hydrate([$primaryKey => $id])->{$primaryKey};
        }

        return $model;
    }

    public function update(array $attributes): int
    {
        $modelClass = $this->modelClass;
        $model = new $modelClass();
        $values = $model->storableInput($attributes);

        if ($values === []) {
            return 0;
        }

        return $this->query->update($values);
    }

    public function delete(): int
    {
        return $this->query->delete();
    }

    public function toSql(): string
    {
        return $this->query->toSql();
    }
}
