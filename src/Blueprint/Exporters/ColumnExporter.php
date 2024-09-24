<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\ColumnDefinition;

class ColumnExporter extends Exporter
{
    /** @var array<string,mixed> */
    protected array $attributes;

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
        $nonCollapsables = $this->runCollapsables();
        $modifiers = $this->extractModifiers($this->attributes);

        if (
            in_array($this->definition->type, ['char', 'string']) &&
            $this->definition->length === Builder::$defaultStringLength
        ) {
            unset($this->attributes['length']);
        }

        if (empty($this->attributes)) {
            $nonCollapsableModifiers = $this->extractModifiers($nonCollapsables);

            if (null !== $this->collapsedType && empty($nonCollapsables)) {
                $type = $this->collapsedType;
                $modifiers = $nonCollapsableModifiers;
            } else {
                $type = $this->definition->type;
            }

            $parameters = [$this->definition->name];

            if (
                in_array($type, ['id', 'remember_token']) ||
                (
                    'softDeletes' === $type &&
                    'deleted_at' === $this->definition->name
                )
            ) {
                $parameters = [];
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
        $attributes = $this->attributes;

        $this->collapseUnsigned($attributes);
        $this->collapseSoftDeletes($attributes);
        $this->collapseRememberToken($attributes);

        return $attributes;
    }

    protected function collapseId()
    {
        if ('bigIncrements' !== $this->collapsedType) {
            return;
        }

        if ('id' === $this->definition->name) {
            $this->collapsedType = 'id';
        }
    }

    protected function collapseIncrements(&$attributes)
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

        if (true === ($attributes['autoIncrement'] ?? null)) {
            $this->collapsedType = match ($this->collapsedType) {
                'unsignedTinyInteger' => 'tinyIncrements',
                'unsignedSmallInteger' => 'smallIncrements',
                'unsignedMediumInteger' => 'mediumIncrements',
                'unsignedInteger' => 'increments',
                'unsignedBigInteger' => 'bigIncrements',
            };
            unset($attributes['autoIncrement']);
        }

        $this->collapseId();
    }

    protected function collapseUnsigned(&$attributes)
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

        if (true === ($attributes['unsigned'] ?? null)) {
            $this->collapsedType = match ($this->definition->type) {
                'tinyInteger' => 'unsignedTinyInteger',
                'smallInteger' => 'unsignedSmallInteger',
                'mediumInteger' => 'unsignedMediumInteger',
                'integer' => 'unsignedInteger',
                'bigInteger' => 'unsignedBigInteger',
            };
            unset($attributes['unsigned']);
        }

        $this->collapseIncrements($attributes);
    }

    protected function collapseSoftDeletes(&$attributes)
    {
        if (
            !in_array($this->definition->type, [
                'timestamp',
                'timestampTz',
            ])
        ) {
            return;
        }

        if (true === ($attributes['nullable'] ?? null) && 'deleted_at' === $this->definition->name) {
            $this->collapsedType = match ($this->definition->type) {
                'timestamp' => 'softDeletes',
                'timestampTz' => 'softDeletesTz',
            };
            unset($attributes['nullable']);
        }
    }

    protected function collapseRememberToken(&$attributes)
    {
        if ('string' !== $this->definition->type) {
            return;
        }

        if (100 === ($attributes['length'] ?? null) && 'remember_token' === $this->definition->name) {
            $this->collapsedType = 'rememberToken';
            unset($attributes['length']);
        }
    }

    public function getCollapsedType()
    {
        return $this->collapsedType;
    }
}
