<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use ReflectionProperty;
use Illuminate\Support\Str;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class ForeignKey extends MigrationAttribute implements AppliesToBlueprint
{
    protected string|Model $on;
    protected ?array $columns;

    protected ?array $references;

    protected $referenceTableName;

    public function __construct(
        string $on,
        null|array|string $column = null,
        null|array|string $references = null,
        protected ?string $onUpdate = null,
        protected ?string $onDelete = null
    ) {
        if (class_exists($on) && is_a($on, Model::class, true)) {
            /** @var Model */
            $instance = new $on();
            $this->on = $instance;
        } else {
            $this->on = $on;
        }

        $this->columns = is_string($column) ? [$column] : $column;
        $this->references = is_string($references) ? [$references] : $references;
    }

    public function getReferenceTableName()
    {
        if (null === $this->referenceTableName) {
            $this->referenceTableName = is_a($this->on, Model::class) ? $this->on->getTable() : $this->on;
        }

        return $this->referenceTableName;
    }

    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
        if (null !== $this->columns && null !== $this->references) {
            return;
        }

        if (null === $this->columns) {
            $this->columns = [Str::snake($reflection->getName())];
        }

        $this->inferFromReflectionClass($reflection->getDeclaringClass());
    }

    public function inferFromReflectionClass(ReflectionClass $reflection): void
    {
        if (
            (null !== $this->references && null !== $this->columns) ||
            !is_a($this->on, Model::class)
        ) {
            return;
        }

        $this->columns = $this->columns ?? [$this->on->getForeignKey()];
        $this->references = $this->references ?? [$this->on->getKeyName()];
    }

    public function inferFromExistingData(): void
    {
        if (null !== $this->columns) {
            return;
        }

        $this->columns = [Str::snake(Str::singular($this->getReferenceTableName())) . '_id'];
    }

    protected function getReferencedModelName(array $modelNames): string
    {
        if (is_a($this->on, Model::class)) {
            return $this->on::class;
        }

        foreach ($modelNames as $modelName) {
            if ((new $modelName())->getTable() === $this->getReferenceTableName()) {
                return $modelName;
            }
        }

        return ''; // @codeCoverageIgnore
    }

    protected function ensureColumn($columnName, Blueprint $table, array $blueprints, array $modelNames): void
    {
        foreach ($table->getColumns() as $column) {
            if ($column->name === $columnName) {
                return;
            }
        }

        try {
            $parameters = [];

            /** @var Blueprint */
            $referencedTable = $blueprints[$this->getReferenceTableName()];
            try {
                foreach ($referencedTable->getColumns() as $column) {
                    if ($column->name !== $this->references[0]) {
                        continue;
                    }

                    $parameters = $column->getAttributes();
                    unset($parameters['name'], $parameters['type']);
                    $table->addColumn($column->type, $columnName, $parameters);
                    return;
                }

                throw new Exception();
            } catch (Exception $e) {
                $propertyReflection = new ReflectionProperty(
                    $this->getReferencedModelName($modelNames),
                    $this->references[0]
                );

                $propertyType = $propertyReflection->getType();

                $type = $propertyType ? Column::TYPE_MAP[$propertyType->getName()] : 'unsignedBigInteger';

                $table->$type($columnName);
                $referencedTable->$type($this->references[0]);
                return;
            }
        } catch (Exception $e) {
            $table->unsignedBigInteger($columnName);
        }
    }

    public function ensureColumns(Blueprint $table, array $blueprints, array $modelNames): void
    {
        if (count($this->columns ?? []) !== 1) {
            foreach ($this->columns ?? [] as $column) {
                $this->ensureColumn($column, $table, $blueprints, $modelNames);
            }

            return;
        }

        $columnName = $this->columns[0];

        $this->ensureColumn($columnName, $table, $blueprints, $modelNames);
    }

    public function applyToBlueprint(Blueprint $table): Blueprint
    {
        $references = is_array($this->references) && count($this->references) === 1
            ? $this->references[0]
            : $this->references;

        $table->foreign($this->columns)
            ->references($references)
            ->on($this->getReferenceTableName())
            ->onUpdate($this->onUpdate)
            ->onDelete($this->onDelete);

        return $table;
    }
}
