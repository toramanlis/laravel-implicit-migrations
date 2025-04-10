<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\IndexType;

class ColumnExporter extends Exporter
{
    protected const PARAMETER_DEFAULTS = [
        'float' => ['precision' => 53],
        'decimal' => ['places' => 2, 'total' => 8],
        'geography' => ['srid' => 4326, 'subtype' => null],
    ];

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

    protected function buildIndexModifiers(bool $up = true)
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

    protected function getParameters(): array
    {
        $parameters = [];
        foreach (Column::PARAMETER_MAP[$this->definition->type] ?? [] as $parameterName) {
            $parameters[$parameterName] = $this->attributes[$parameterName] ?? null;
        }

        foreach (array_reverse($parameters) as $parameterName => $value) {
            if (is_null($value)) {
                unset($parameters[$parameterName]);
            } else {
                break;
            }
        }

        return $parameters;
    }

    protected function removeDefaultParameters(&$parameters)
    {
        switch ($this->definition->type) {
            case 'char':
            case 'string':
                if ($this->definition->length === Builder::$defaultStringLength) {
                    unset($parameters['length']);
                }
                break;
            case 'time':
            case 'timeTz':
            case 'timestamp':
            case 'timestampTz':
            case 'dateTime':
            case 'dateTimeTz':
                if ($this->definition->precision === Builder::$defaultTimePrecision) {
                    unset($parameters['precision']);
                }
                break;
            default:
                foreach (static::PARAMETER_DEFAULTS[$this->definition->type] ?? [] as $name => $value) {
                    if (($parameters[$name] ?? []) === $value) {
                        unset($parameters[$name]);
                    }
                }
        }
    }

    protected function exportUp(): string
    {
        $indexModifiers = $this->buildIndexModifiers();

        if (
            in_array($this->definition->type, ['char', 'string']) &&
            $this->definition->length === Builder::$defaultStringLength
        ) {
            unset($this->attributes['length']);
        }

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
            $parameters = $this->getParameters();
            $modifiers = array_merge($this->extractModifiers($this->attributes), $indexModifiers);

            if (empty(array_diff(array_keys($this->attributes), array_keys($parameters)))) {
                array_unshift($parameters, $this->definition->name);
                $this->removeDefaultParameters($parameters);
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
