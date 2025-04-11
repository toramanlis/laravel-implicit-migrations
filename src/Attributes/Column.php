<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Exception as BaseException;
use Illuminate\Database\Schema\Blueprint;
use ReflectionProperty;
use Illuminate\Support\Str;
use ReflectionNamedType;
use ReflectionType;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Column extends MigrationAttribute
{
    use Applicable;

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

    public const SUPPORTED_MODIFIERS = [
        'after',
        'autoIncrement',
        'charset',
        'collation',
        'comment',
        'default',
        'first',
        'from',
        'invisible',
        'nullable',
        'storedAs',
        'unsigned',
        'useCurrent',
        'useCurrentOnUpdate',
        'virtualAs',
        'generatedAs',
        'always',
    ];

    public const SUPPORTED_ATTRIBUTES = [
        'after',
        'autoIncrement',
        'comment',
        'default',
        'nullable',
        'storedAs',
        'unsigned',
        'virtualAs',
        'length',
        'precision',
        'total',
        'places',
        'allowed',
        'fixed',
        'subtype',
        'srid',
        'expression',
        'collation',
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
            throw new Exception(Exception::CODE_COL_NO_NAME, [$table->getTable()]);
        }

        if (null === $this->type) {
            throw new Exception(Exception::CODE_COL_NO_TYPE, [$table->getTable(), $this->name]);
        }
    }

    public static function getParameters($type, $attributes): array
    {
        $parameters = [];
        foreach (static::PARAMETER_MAP[$type] ?? [] as $parameterName) {
            if (!array_key_exists($parameterName, $attributes)) {
                continue;
            }

            $parameters[$parameterName] = $attributes[$parameterName];
        }

        return $parameters;
    }

    protected function process(Blueprint $table): Blueprint
    {
        try {
            $this->validate($table);
        } catch (Exception $e) {
            if ($this->inferred) {
                return $table;
            }

            throw $e;
        } catch (BaseException $e) {
            $reportedName = $this->name ?? '???';
            throw new Exception(
                Exception::CODE_COL_GENERIC,
                [$table->getTable(), $reportedName],
                $e
            );
        }

        $attributes = [];

        foreach (static::SUPPORTED_ATTRIBUTES as $attributeName) {
            if (null === $this->{$attributeName}) {
                continue;
            }

            $attributes[$attributeName] = $this->{$attributeName};
        }

        $parameters = static::getParameters($this->type, $attributes);

        if (
            !empty(array_diff(
                array_keys($attributes),
                static::SUPPORTED_MODIFIERS,
                array_keys($parameters)
            ))
        ) {
            $table->addColumn($this->type, $this->name, $attributes);
            return $table;
        }

        $column = $table->{$this->type}($this->name, ...$parameters);

        foreach (static::SUPPORTED_MODIFIERS as $modifier) {
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
