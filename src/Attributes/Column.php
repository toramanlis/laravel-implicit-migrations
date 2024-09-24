<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use ReflectionProperty;
use Illuminate\Support\Str;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\ColumnExporter;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column extends MigrationAttribute
{
    public const TYPE_MAP = [
        'array' => 'json',
        'bool' => 'boolean',
        'float' => 'decimal',
        'int' => 'integer',
        'object' => 'json',
        'string' => 'string',
        'iterable' => 'json',
    ];

    public const PARAMETER_MAP = [
        'char' => ['length'],
        'string' => ['length'],
        'integer' => ['autoIncrement', 'unsigned'],
        'tinyInteger' => ['autoIncrement', 'unsigned'],
        'smallInteger' => ['autoIncrement', 'unsigned'],
        'mediumInteger' => ['autoIncrement', 'unsigned'],
        'bigInteger' => ['autoIncrement', 'unsigned'],
        'float' => ['precision'],
        'decimal' => ['total', 'places'],
        'enum' => ['allowed'],
        'set' => ['allowed'],
        'dateTime' => ['precision'],
        'dateTimeTz' => ['precision'],
        'time' => ['precision'],
        'timeTz' => ['precision'],
        'timestamp' => ['precision'],
        'timestampTz' => ['precision'],
        'binary' => ['length', 'fixed'],
        'geometry' => ['subtype', 'srid'],
        'geography' => ['subtype', 'srid'],
        'computed' => ['expression'],
    ];

    public function __construct(
        protected ?string $name = null,
        protected ?string $type = null,
        protected ?bool $nullable = null,
        protected $default = null,
        protected ?int $length = null,
        protected ?bool $unsigned = null,
        protected ?bool $autoIncrement = null,
        protected ?int $precision = null,
        protected ?int $total = null,
        protected ?int $places = null,
        protected ?array $allowed = null,
        protected ?bool $fixed = null,
        protected ?string $subtype = null,
        protected ?int $srid = null,
        protected ?string $expression = null,
        protected ?string $collation = null,
        protected ?string $comment = null,
        protected ?string $virtualAs = null,
        protected ?string $storedAs = null,
        protected ?string $after = null
    ) {
    }

    public function after($after, $force = false): static
    {
        if ($force || null === $this->after) {
            $this->after = $after;
        }

        return $this;
    }

    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
        if (
            null !== $this->name &&
            null !== $this->type &&
            null !== $this->nullable &&
            null !== $this->default
        ) {
            return;
        }

        if (null === $this->name) {
            $this->name = Str::snake($reflection->getName());
        }

        if (null === $this->default && $reflection->hasDefaultValue()) {
            $this->default = $reflection->getDefaultValue();
        }

        if (!$reflection->hasType()) {
            return;
        }

        /** @var ReflectionType */
        $reflectionType = $reflection->getType();

        if (null === $this->type && $reflectionType instanceof ReflectionNamedType) {
            $this->type = static::TYPE_MAP[$reflectionType->getName()] ?? null;
        }

        if (null === $this->nullable) {
            $this->nullable = $reflectionType->allowsNull() ?: null;
        }
    }

    public function applyToBlueprint(Blueprint $table): Blueprint
    {
        $attributes = array_filter([
            'nullable' => $this->nullable,
            'default' => $this->default,
            'length' => $this->length,
            'unsigned' => $this->unsigned,
            'autoIncrement' => $this->autoIncrement,
            'precision' => $this->precision,
            'total' => $this->total,
            'places' => $this->places,
            'allowed' => $this->allowed,
            'fixed' => $this->fixed,
            'subtype' => $this->subtype,
            'srid' => $this->srid,
            'expression' => $this->expression,
            'collation' => $this->collation,
            'comment' => $this->comment,
            'virtualAs' => $this->virtualAs,
            'storedAs' => $this->storedAs,
            'after' => $this->after,
        ], fn ($i) => null !== $i);

        if (isset(static::PARAMETER_MAP[$this->type])) {
            $parameters = [];
            foreach (static::PARAMETER_MAP[$this->type] as $parameterName) {
                if (!isset($attributes[$parameterName])) {
                    continue;
                }

                $parameters[$parameterName] = $attributes[$parameterName];
            }

            if (
                !empty(array_diff(
                    array_keys($attributes),
                    ColumnExporter::SUPPORTED_MODIFIERS,
                    array_keys($parameters)
                ))
            ) {
                $table->addColumn($this->type, $this->name, $attributes);
                return $table;
            }

            $column = $table->{$this->type}($this->name, ...$parameters);

            foreach (ColumnExporter::SUPPORTED_MODIFIERS as $modifier) {
                if (!isset($attributes[$modifier])) {
                    continue;
                }

                if (false !== $attributes[$modifier]) {
                    $column->$modifier($attributes[$modifier]);
                }
            }
        }

        return $table;
    }
}
