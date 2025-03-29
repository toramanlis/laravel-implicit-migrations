<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Exception;
use Illuminate\Database\Schema\Blueprint;
use ReflectionProperty;
use Illuminate\Support\Str;
use ReflectionNamedType;
use ReflectionType;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\ColumnExporter;
use Toramanlis\ImplicitMigrations\Exceptions\ImplicationException;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
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

    protected bool $inferred = false;

    public function __construct(
        protected ?string $type = null,
        protected ?string $name = null,
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

    public function setInferred(bool $value = true)
    {
        $this->inferred = $value;
    }

    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
        $this->name = Str::snake($reflection->getName());

        if (
            null !== $this->type &&
            null !== $this->nullable &&
            null !== $this->default
        ) {
            return;
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

    protected function validate(Blueprint $table)
    {
        if (empty($this->name)) {
            throw new ImplicationException(ImplicationException::CODE_COL_NO_NAME, [$table->getTable()]);
        }

        if (null === $this->type) {
            throw new ImplicationException(ImplicationException::CODE_COL_NO_TYPE, [$table->getTable(), $this->name]);
        }
    }

    public function applyToBlueprint(Blueprint $table): Blueprint
    {
        try {
            $this->validate($table);
        } catch (ImplicationException $e) {
            if ($this->inferred) {
                return $table;
            }

            throw $e;
        } catch (Exception $e) {
            $reportedName = $this->name ?? '???';
            throw new ImplicationException(
                ImplicationException::CODE_COL_GENERIC,
                [$table->getTable(), $reportedName],
                $e
            );
        }

        $attributes = array_filter([
            'after' => $this->after,
            'autoIncrement' => $this->autoIncrement,
            'comment' => $this->comment,
            'default' => $this->default,
            'nullable' => $this->nullable,
            'storedAs' => $this->storedAs,
            'unsigned' => $this->unsigned,
            'virtualAs' => $this->virtualAs,
            'length' => $this->length,
            'precision' => $this->precision,
            'total' => $this->total,
            'places' => $this->places,
            'allowed' => $this->allowed,
            'fixed' => $this->fixed,
            'subtype' => $this->subtype,
            'srid' => $this->srid,
            'expression' => $this->expression,
            'collation' => $this->collation,
        ], fn ($i) => null !== $i);

        $parameters = [];
        foreach (static::PARAMETER_MAP[$this->type] ?? [] as $parameterName) {
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

        return $table;
    }
}
