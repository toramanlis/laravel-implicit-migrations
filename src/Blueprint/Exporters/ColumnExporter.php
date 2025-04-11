<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Fluent;
use ReflectionMethod;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\IndexType;

class ColumnExporter extends Exporter
{
    protected static $parameterDefaults = [];

    /** @var array<string,mixed> */
    protected array $attributes;

    /** @var array<string,mixed> */
    protected array $collapsedAttributes;

    protected ?string $collapsedType = null;

    public ?Fluent $foreignKey = null;

    public const SUPPORTED_MODIFIERS = Column::SUPPORTED_MODIFIERS;

    public function __construct(public readonly ColumnDefinition $definition)
    {
        $this->attributes = array_filter($this->definition->getAttributes());
        unset(
            $this->attributes['type'],
            $this->attributes['name'],
            $this->attributes['change'],
            $this->attributes[IndexType::Primary->value],
            $this->attributes[IndexType::Unique->value],
            $this->attributes[IndexType::Index->value]
        );

        $this->runCollapsables();
    }

    public function setForeignKey(Fluent $foreignKey)
    {
        if ('unsignedBigInteger' !== $this->collapsedType || !empty($this->collapsedAttributes)) {
            return false;
        }

        $foreignKey->references = 'id' === $foreignKey->references ? null : $foreignKey->references;
        $this->foreignKey = $foreignKey;
        return true;
    }

    protected function buildIndexModifiers()
    {
        $modifiers = [];

        foreach ([IndexType::Primary->value, IndexType::Unique->value, IndexType::Index->value] as $indexType) {
            if (!$this->definition->$indexType) {
                continue;
            }

            $parameters = true === $this->definition->$indexType ? [] : [$this->definition->$indexType];
            $modifiers[] = static::makeModifier($indexType, $parameters);
        }

        return $modifiers;
    }

    protected static function getParameterDefaults($type)
    {
        if (isset(static::$parameterDefaults[$type])) {
            return static::$parameterDefaults[$type];
        }

        switch ($type) {
            case 'char':
            case 'string':
                static::$parameterDefaults['string'] = ['length' => Builder::$defaultStringLength];
                return static::$parameterDefaults['string'];
            case 'time':
            case 'timeTz':
            case 'timestamp':
            case 'timestampTz':
            case 'dateTime':
            case 'dateTimeTz':
                static::$parameterDefaults[$type] = ['precision' => Builder::$defaultTimePrecision];
                return static::$parameterDefaults[$type];
            case 'float':
                $argumentNames = ['precision'];
                break;
            case 'decimal':
                $argumentNames = ['total', 'places'];
                break;
            case 'geography':
                $argumentNames = ['subtype', 'srid'];
                break;
            default:
                static::$parameterDefaults[$type] = [];
                return static::$parameterDefaults[$type];
        }

        $reflectionMethod = new ReflectionMethod(Blueprint::class, $type);
        $reflectionParameters = $reflectionMethod->getParameters();

        static::$parameterDefaults[$type] = [];
        foreach ($reflectionParameters as $reflectionParameter) {
            $argumentName = $reflectionParameter->getName();

            if (!$reflectionParameter->isDefaultValueAvailable() || !in_array($argumentName, $argumentNames)) {
                continue;
            }

            static::$parameterDefaults[$type][$argumentName] = $reflectionParameter->getDefaultValue();
        }

        return static::$parameterDefaults[$type];
    }

    public static function removeDefaultParameters($type, $parameters)
    {
        $cleaned = [];
        $defaults = static::getParameterDefaults($type);

        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $parameters)) {
                $value = $parameters[$key];
                unset($parameters[$key]);
            }

            $parameters[$key] = $value;
        }

        $canBePositional = true;

        foreach ($parameters as $key => $value) {
            if (array_key_exists($key, $defaults) && $defaults[$key] === $value) {
                $canBePositional = false;
                continue;
            }

            if ($canBePositional) {
                $cleaned[] = $value;
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }

    protected function exportUp(): string
    {
        $indexModifiers = $this->buildIndexModifiers();

        if (null !== $this->collapsedType && empty($this->collapsedAttributes)) {
            $type = $this->collapsedType;
            $modifiers = array_merge($this->extractModifiers($this->collapsedAttributes), $indexModifiers);

            $parameters = in_array($type, ['id', 'rememberToken', 'softDeletes']) ? [] : [$this->definition->name];
            if ('id' === $type && 'id' !== $this->definition->name) {
                $parameters[] = $this->definition->name;
            }

            if ($this->foreignKey && 'unsignedBigInteger' === $type) {
                $modifiers = array_merge([static::makeModifier('constrained', array_filter([
                        $this->foreignKey->on,
                        'id' == $this->foreignKey->references ? null : $this->foreignKey->references,
                        $this->foreignKey->index
                    ]))], $modifiers);
                return $this->exportMethodCall('foreignId', $parameters, $modifiers);
            }

            return $this->exportMethodCall($type, $parameters, $modifiers);
        } else {
            $parameters = Column::getParameters($this->definition->type, $this->attributes);
            $modifiers = array_merge($this->extractModifiers($this->attributes), $indexModifiers);

            if (empty(array_diff(array_keys($this->attributes), array_keys($parameters)))) {
                array_unshift($parameters, $this->definition->name);
                $parameters = static::removeDefaultParameters($this->definition->type, $parameters);
                return $this->exportMethodCall($this->definition->type, $parameters, $modifiers);
            }

            return $this->exportMethodCall('addColumn', [
                $this->definition->type,
                $this->definition->name,
                $this->attributes,
            ], $modifiers);
        }
    }

    protected function exportDown(): string
    {
        return $this->exportMethodCall('dropColumn', [$this->definition->name]);
    }

    protected function runCollapsables()
    {
        $this->collapsedAttributes = $this->attributes;

        $this->collapseUnsigned();
        $this->collapseSoftDeletes();
        $this->collapseRememberToken();
    }

    protected function collapseId()
    {
        if ('bigIncrements' !== $this->collapsedType) {
            return;
        }

        $this->collapsedType = 'id';
    }

    protected function collapseIncrements()
    {
        if (
            !in_array($this->collapsedType, [
                'unsignedTinyInteger',
                'unsignedSmallInteger',
                'unsignedMediumInteger',
                'unsignedInteger',
                'unsignedBigInteger'
            ])
        ) {
            return;
        }

        if (true === ($this->collapsedAttributes['autoIncrement'] ?? null)) {
            $this->collapsedType = match ($this->collapsedType) {
                'unsignedTinyInteger' => 'tinyIncrements',
                'unsignedSmallInteger' => 'smallIncrements',
                'unsignedMediumInteger' => 'mediumIncrements',
                'unsignedInteger' => 'increments',
                'unsignedBigInteger' => 'bigIncrements',
            };
            unset($this->collapsedAttributes['autoIncrement']);
        }

        $this->collapseId();
    }

    protected function collapseUnsigned()
    {
        if (
            !in_array($this->definition->type, [
                'tinyInteger',
                'smallInteger',
                'mediumInteger',
                'integer',
                'bigInteger'
            ])
        ) {
            return;
        }

        if (true === ($this->collapsedAttributes['unsigned'] ?? null)) {
            $this->collapsedType = match ($this->definition->type) {
                'tinyInteger' => 'unsignedTinyInteger',
                'smallInteger' => 'unsignedSmallInteger',
                'mediumInteger' => 'unsignedMediumInteger',
                'integer' => 'unsignedInteger',
                'bigInteger' => 'unsignedBigInteger',
            };
            unset($this->collapsedAttributes['unsigned']);
        }

        $this->collapseIncrements($this->collapsedAttributes);
    }

    protected function collapseSoftDeletes()
    {
        if (
            !in_array($this->definition->type, [
                'timestamp',
                'timestampTz',
            ])
        ) {
            return;
        }

        if (true === ($this->collapsedAttributes['nullable'] ?? null) && 'deleted_at' === $this->definition->name) {
            $this->collapsedType = match ($this->definition->type) {
                'timestamp' => 'softDeletes',
                'timestampTz' => 'softDeletesTz',
            };
            unset($this->collapsedAttributes['nullable']);
        }
    }

    protected function collapseRememberToken()
    {
        if ('string' !== $this->definition->type) {
            return;
        }

        if (
            100 === ($this->collapsedAttributes['length'] ?? null) &&
            'remember_token' === $this->definition->name &&
            $this->definition->nullable
        ) {
            $this->collapsedType = 'rememberToken';
            unset($this->collapsedAttributes['length']);
        }
    }

    public function getCollapsedType()
    {
        return $this->collapsedType;
    }
}
