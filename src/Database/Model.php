<?php

namespace Shift\Database;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;
use Shift\Database\Attributes\Cast;
use Shift\Database\Attributes\Guarded;
use Shift\Database\Attributes\PrimaryKey;

abstract class Model
{
    protected string $table = '';

    private static ?Database $database = null;

    public static function useDatabase(Database $database): void
    {
        self::$database = $database;
    }

    public static function query(?Database $database = null): ModelQueryBuilder
    {
        return new ModelQueryBuilder(static::class, $database ?? self::database());
    }

    public static function find(mixed $id, ?Database $database = null): ?static
    {
        return static::query($database)->find($id);
    }

    public static function create(array $attributes, ?Database $database = null): static
    {
        return static::query($database)->create($attributes);
    }

    public static function hydrate(array $attributes): static
    {
        $model = new static();
        $model->fill($attributes, includeGuarded: true);

        return $model;
    }

    public function fill(array $attributes, bool $includeGuarded = false): static
    {
        foreach ($attributes as $column => $value) {
            if (!$includeGuarded && $this->isGuarded($column)) {
                continue;
            }

            if (!$this->hasColumn($column)) {
                continue;
            }

            $this->{$column} = $this->castFromDatabase($column, $value);
        }

        return $this;
    }

    public function forceFill(array $attributes): static
    {
        return $this->fill($attributes, includeGuarded: true);
    }

    public function save(?Database $database = null): bool
    {
        $database ??= self::database();
        $primaryKey = static::primaryKey();
        $attributes = $this->storableAttributes();
        $id = $this->{$primaryKey} ?? null;

        if ($id !== null) {
            unset($attributes[$primaryKey]);

            if ($attributes === []) {
                return true;
            }

            return $database->table(static::table())
                ->where($primaryKey, $id)
                ->update($attributes) > 0;
        }

        if (($attributes[$primaryKey] ?? null) === null) {
            unset($attributes[$primaryKey]);
        }

        $id = $database->table(static::table())->insertGetId($attributes);

        if ($this->hasColumn($primaryKey)) {
            $this->{$primaryKey} = $this->castFromDatabase($primaryKey, $id);
        }

        return true;
    }

    public function delete(?Database $database = null): int
    {
        $primaryKey = static::primaryKey();
        $id = $this->{$primaryKey} ?? null;

        if ($id === null) {
            return 0;
        }

        return static::query($database)->where($primaryKey, $id)->delete();
    }

    public function toArray(): array
    {
        $values = [];

        foreach (static::columns() as $column => $property) {
            if (!$property->isInitialized($this)) {
                continue;
            }

            $values[$column] = $this->{$column};
        }

        return $values;
    }

    public static function table(): string
    {
        $defaults = (new ReflectionClass(static::class))->getDefaultProperties();
        $table = $defaults['table'] ?? '';

        if (is_string($table) && $table !== '') {
            return $table;
        }

        return static::defaultTableName();
    }

    public static function primaryKey(): string
    {
        foreach (static::columns() as $column => $property) {
            if ($property->getAttributes(PrimaryKey::class) !== []) {
                return $column;
            }
        }

        return array_key_exists('id', static::columns()) ? 'id' : 'id';
    }

    public function storableAttributes(): array
    {
        $values = [];

        foreach (static::columns() as $column => $property) {
            if (!$property->isInitialized($this)) {
                continue;
            }

            $values[$column] = $this->castForDatabase($column, $this->{$column});
        }

        return $values;
    }

    public function storableInput(array $attributes, bool $includeGuarded = false): array
    {
        $values = [];

        foreach ($attributes as $column => $value) {
            if (!$includeGuarded && $this->isGuarded($column)) {
                continue;
            }

            if (!$this->hasColumn($column)) {
                continue;
            }

            $values[$column] = $this->castForDatabase($column, $value);
        }

        return $values;
    }

    public static function guardedColumns(): array
    {
        $guarded = [];

        foreach (static::columns() as $column => $property) {
            if ($property->getAttributes(Guarded::class) !== []) {
                $guarded[] = $column;
            }
        }

        return $guarded;
    }

    /**
     * @return array<string, ReflectionProperty>
     */
    public static function columns(): array
    {
        $reflection = new ReflectionClass(static::class);
        $columns = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $columns[$property->getName()] = $property;
        }

        return $columns;
    }

    private static function database(): Database
    {
        if (!self::$database instanceof Database) {
            throw new DatabaseException('No database configured for model queries.');
        }

        return self::$database;
    }

    private function hasColumn(string $column): bool
    {
        return array_key_exists($column, static::columns());
    }

    private function isGuarded(string $column): bool
    {
        return in_array($column, static::guardedColumns(), true);
    }

    private function castFromDatabase(string $column, mixed $value): mixed
    {
        $cast = $this->castForColumn($column);

        if ($cast === null || $value === null) {
            return $value;
        }

        return match (strtolower($cast->type)) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'array' => is_array($value) ? $value : (json_decode((string) $value, true) ?: []),
            'datetime', 'date' => $value instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($value)
                : new DateTimeImmutable((string) $value),
            default => $this->castClassFromDatabase($cast->type, $value),
        };
    }

    private function castForDatabase(string $column, mixed $value): mixed
    {
        $cast = $this->castForColumn($column);

        if ($cast === null || $value === null) {
            return $value;
        }

        return match (strtolower($cast->type)) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'array' => json_encode($value, JSON_THROW_ON_ERROR),
            'datetime' => $value instanceof DateTimeInterface
                ? $value->format($cast->format ?? DateTimeInterface::ATOM)
                : $value,
            'date' => $value instanceof DateTimeInterface
                ? $value->format($cast->format ?? 'Y-m-d')
                : $value,
            default => $this->castClassForDatabase($value),
        };
    }

    private function castForColumn(string $column): ?Cast
    {
        $property = static::columns()[$column] ?? null;

        if (!$property instanceof ReflectionProperty) {
            return null;
        }

        $attributes = $property->getAttributes(Cast::class);

        if ($attributes === []) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    private function castClassFromDatabase(string $class, mixed $value): mixed
    {
        if (!class_exists($class)) {
            return $value;
        }

        if (is_a($value, $class)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        if (method_exists($class, 'fromArray') && is_array($value)) {
            return $class::fromArray($value);
        }

        return new $class($value);
    }

    private function castClassForDatabase(mixed $value): mixed
    {
        if ($value instanceof JsonSerializable) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return json_encode($value->toArray(), JSON_THROW_ON_ERROR);
        }

        return $value;
    }

    private static function defaultTableName(): string
    {
        $shortName = (new ReflectionClass(static::class))->getShortName();
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));

        return str_ends_with($snake, 's') ? $snake : $snake . 's';
    }
}
