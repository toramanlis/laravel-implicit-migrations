<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\ColumnDefinition;

class ColumnExporter extends Exporter
{
    /** @var array<string,mixed> */
    protected array $attributes;

    /** @var array<string,mixed> */
    protected array $collapsedAttributes;

    protected ?string $collapsedType = null;

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

    public function __construct(protected ColumnDefinition $definition)
    {
    }

    protected function exportUp(): string
    {
        $this->attributes = array_filter($this->definition->getAttributes());
        unset($this->attributes['type'], $this->attributes['name']);

        $this->runCollapsables();
        $modifiers = $this->extractModifiers($this->attributes);
        $collapsedModifiers = $this->extractModifiers($this->collapsedAttributes);

        if (
            in_array($this->definition->type, ['char', 'string']) &&
            $this->definition->length === Builder::$defaultStringLength
        ) {
            unset($this->attributes['length']);
        }

        $isCollapsable = null !== $this->collapsedType && empty($this->collapsedAttributes);

        if (empty($this->attributes) || $isCollapsable) {
            if ($isCollapsable) {
                $type = $this->collapsedType;
                $modifiers = $collapsedModifiers;
            } else {
                $type = $this->definition->type;
            }

            $parameters = in_array($type, ['id', 'rememberToken', 'softDeletes']) ? [] : [$this->definition->name];
            if ('id' === $type && 'id' !== $this->definition->name) {
                $parameters[] = $this->definition->name;
            }

            return $this->exportMethodCall($type, $parameters, $modifiers);
        } else {
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

        if (100 === ($this->collapsedAttributes['length'] ?? null) && 'remember_token' === $this->definition->name) {
            $this->collapsedType = 'rememberToken';
            unset($this->collapsedAttributes['length']);
        }
    }

    public function getCollapsedType()
    {
        return $this->collapsedType;
    }
}
