<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use ReflectionProperty;
use Illuminate\Support\Str;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class ForeignKey extends MigrationAttribute
{
    protected string|Model $on;
    protected ?array $columns;

    protected ?array $references;

    public function __construct(
        string $on,
        null|array|string $columns = null,
        null|array|string $references = null,
        protected ?string $onUpdate = null,
        protected ?string $onDelete = null
    ) {
        if (class_exists($on) && is_a($on, Model::class, true)) {
            $this->on = new $on();
        } else {
            $this->on = $on;
        }

        $this->columns = is_string($columns) ? [$columns] : $columns;
        $this->references = is_string($references) ? [$references] : $references;
    }

    public function getReferenceTableName()
    {
        static $referenceTableName = null;

        if (null == $referenceTableName) {
            $referenceTableName = is_a($this->on, Model::class) ? $this->on->getTable() : $this->on;
        }

        return $referenceTableName;
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
            null !== $this->references ||
            !is_a($this->on, Model::class)
        ) {
            return;
        }

        if (null === $this->columns) {
            $this->columns = [$this->on->getForeignKey()];
        }

        $this->references = [$this->on->getKeyName()];
    }

    public function inferFromExistingData(): void
    {
        if (null !== $this->columns) {
            return;
        }

        $this->columns = [Str::snake(Str::singular($this->getReferenceTableName())) . '_id'];
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
