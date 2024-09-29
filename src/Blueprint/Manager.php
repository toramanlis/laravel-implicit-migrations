<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Fluent;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\MigrationAttribute;
use Toramanlis\ImplicitMigrations\Attributes\Off;
use Toramanlis\ImplicitMigrations\Attributes\PivotColumn;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
use Toramanlis\ImplicitMigrations\Attributes\Table;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\DirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\IndirectRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\Relationship as RelationshipsRelationship;
use Toramanlis\ImplicitMigrations\Blueprint\Relationships\Polymorphic;
use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Toramanlis\ImplicitMigrations\Generator\RelationshipResolver;

/** @package Toramanlis\ImplicitMigrations\Blueprint */
class Manager
{
    /** @var array<string,RelationshipsRelationship> */
    protected array $relationshipMap = [];

    public function __construct(
        /** @var array<Blueprint> */
        protected array $blueprints
    ) {
    }

    /** @return array<Blueprint>  */
    public function getBlueprints(): array
    {
        return $this->blueprints;
    }

    /** @return array<RelationshipsRelationship>  */
    public function getRelationshipMap(): array
    {
        return $this->relationshipMap;
    }

    protected static function getMigrationAttributes(string $modelName): array
    {
        $reflection = new ReflectionClass($modelName);

        $attributes = [];

        $attributeReflections = $reflection
            ->getAttributes(MigrationAttribute::class, ReflectionAttribute::IS_INSTANCEOF);
        foreach ($attributeReflections as $attributeReflection) {
            $attribute = $attributeReflection->newInstance();
            $attribute->inferFromReflectionClass($reflection);
            $attribute->inferFromExistingData();

            $attributes[] = $attribute;
        }

        foreach ($reflection->getProperties() as $propertyReflection) {
            $attributeReflections = $propertyReflection
                ->getAttributes(MigrationAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

            if (
                0 === count($attributeReflections)
                && !static::isPropertyOff($modelName, $propertyReflection->getName())
            ) {
                $attribute = new Column();
                $attribute->inferFromReflectionProperty($propertyReflection);
                $attribute->inferFromExistingData();
                $attributes[] = $attribute;
            }

            foreach ($attributeReflections as $attributeReflection) {
                $attribute = $attributeReflection->newInstance();
                $attribute->inferFromReflectionProperty($propertyReflection);
                $attribute->inferFromExistingData();

                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**
     * @param array<ImplicitMigration> $migrations
     * @return array<string,Blueprint>
     */
    public static function mergeMigrationsToBlueprints(array $migrations): array
    {
        $blueprints = [];
        foreach ($migrations as $migration) {
            $blueprints[$migration->getSource()] = $blueprints[$migration->getSource()]
                ?? new SimplifyingBlueprint($migration->getTableNameNew());

            $migration->tableUp($blueprints[$migration->getSource()]);
        }

        return $blueprints;
    }

    /**
     * @param string $modelName
     * @return array<RelationshipsRelationship>
     */
    public static function getRelationships(string $modelName): array
    {
        $modelReflection = new ReflectionClass($modelName);
        $modelInstance = new $modelName();

        $relationships = [];

        foreach ($modelReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $methodReflection) {
            if (
                $methodReflection->isAbstract()
                || $methodReflection->isStatic()
                || $methodReflection->getNumberOfRequiredParameters()
            ) {
                continue;
            }

            if (
                !count($methodReflection->getAttributes(Relationship::class))
                && (
                    static::isMethodOff($modelName, $methodReflection->getShortName())
                    || !$methodReflection->hasReturnType()
                    || !is_a((string) $methodReflection->getReturnType(), Relation::class, true)
                )
            ) {
                continue;
            }

            $methodName = $methodReflection->getShortName();
            $methodRelationships = RelationshipResolver::resolve($modelInstance->$methodName());

            foreach ($methodRelationships as $relationship) {
                $relationship->setSource("{$modelName}::{$methodName}");
            }

            $relationships = array_merge($relationships, $methodRelationships);

            if (
                count($methodRelationships) !== 1
                || !$methodRelationships[0] instanceof IndirectRelationship
            ) {
                continue;
            }

            $relationship = $methodRelationships[0];

            /** @var IndirectRelationship $relationship */

            $pivotColumnAttributes = [];

            foreach ($methodReflection->getAttributes(PivotColumn::class) as $pivotColumnAttribute) {
                $pivotColumnAttributes[] = $pivotColumnAttribute->newInstance();
            }

            $relationship->setPivotColumnAttributes($pivotColumnAttributes);
        }

        return $relationships;
    }

    protected function getBlueprintByTable(string $table): Blueprint
    {
        if (!isset($this->blueprints[$table])) {
            $blueprint = new Blueprint($table);
            $this->blueprints[$table] = $blueprint;
        }

        return $this->blueprints[$table];
    }

    protected static function ensureKeyColumn(Blueprint $blueprint, string $columnName, string $type = 'id')
    {
        foreach ($blueprint->getColumns() as $column) {
            if ($column->name === $columnName) {
                return;
            }
        }

        $blueprint->$type($columnName);
    }

    protected function defineForeignKey(
        string $relatedTable,
        string $foreignKey,
        string $parentTable,
        string $localKey
    ) {
        $blueprint = $this->getBlueprintByTable($relatedTable);
        $parentBlueprint = $this->getBlueprintByTable($parentTable);

        static::ensureKeyColumn($parentBlueprint, $localKey);

        $blueprint->foreign($foreignKey)
        ->references($localKey)
        ->on($parentTable);
    }

    protected function applyRelationshipToBlueprints(RelationshipsRelationship $relationship)
    {
        if ($relationship instanceof DirectRelationship) {
            $blueprint = $this->getBlueprintByTable($relationship->getRelatedTable());
            $this->relationshipMap[$relationship->getRelatedTable()] = $relationship;

            static::ensureKeyColumn($blueprint, $relationship->getForeignKey(), 'unsignedBigInteger');

            if (in_array(Polymorphic::class, class_uses_recursive($relationship))) {
                /** @var Polymorphic $relationship */
                $this->ensureKeyColumn($blueprint, $relationship->getTypeKey(), 'string');
                return;
            }

            $this->relationshipMap[$relationship->getRelatedTable()] = $relationship;
            $this->relationshipMap[$relationship->getParentTable()] = $relationship;

            $this->defineForeignKey(
                $relationship->getRelatedTable(),
                $relationship->getForeignKey(),
                $relationship->getParentTable(),
                $relationship->getLocalKey()
            );
        } elseif ($relationship instanceof IndirectRelationship) {
            $morphableBlueprint = $this
                ->getBlueprintByTable($relationship->getRelatedTables()[0]);
            $blueprint = $this
                ->getBlueprintByTable($relationship->getRelatedTables()[1]);
            $pivotBlueprint = $this
                ->getBlueprintByTable($relationship->getPivotTable());

            [
                $morphableBlueprint->getTable() => $morphableSideForeignKey,
                $blueprint->getTable() => $foreignKey
            ] = $relationship->getForeignKeys();
            [
                $morphableBlueprint->getTable() => $morphableSideLocalKey,
                $blueprint->getTable() => $localKey
            ] = $relationship->getLocalKeys();

            $this->relationshipMap[$blueprint->getTable()] = $relationship;
            $this->relationshipMap[$pivotBlueprint->getTable()] = $relationship;

            $this->ensureKeyColumn($pivotBlueprint, $foreignKey, 'unsignedBigInteger');
            $this->ensureKeyColumn($pivotBlueprint, $morphableSideForeignKey, 'unsignedBigInteger');
            $this->defineForeignKey(
                $relationship->getPivotTable(),
                $foreignKey,
                $blueprint->getTable(),
                $localKey
            );

            foreach ($relationship->pivotColumnAttributes as $attribute) {
                foreach ($pivotBlueprint->getColumns() as $column) {
                    if ($attribute->getName() === $column->name) {
                        continue 2;
                    }
                }

                $attribute->applyToBlueprint($pivotBlueprint);
            }

            if (in_array(Polymorphic::class, class_uses_recursive($relationship))) {
                /** @var Polymorphic $relationship */
                $this->ensureKeyColumn($pivotBlueprint, $relationship->getTypeKey(), 'string');
                return;
            }

            $this->relationshipMap[$morphableBlueprint->getTable()] = $relationship;
            $this->ensureKeyColumn($morphableBlueprint, $morphableSideLocalKey);

            $this->defineForeignKey(
                $relationship->getPivotTable(),
                $morphableSideForeignKey,
                $morphableBlueprint->getTable(),
                $morphableSideLocalKey
            );
        }
    }

    public function applyRelationshipsToBlueprints(array $relationships)
    {
        foreach ($relationships as $relationship) {
            $this->applyRelationshipToBlueprints($relationship);
        }
    }

    protected static function isModelOff(string $modelName): bool
    {
        $modelReflection = new ReflectionClass($modelName);
        $attributes = $modelReflection->getAttributes(Off::class, ReflectionAttribute::IS_INSTANCEOF);

        return !Config::get('database.auto_infer_migrations') || 0 !== count($attributes);
    }

    protected static function isPropertyOff($modelName, $propertyName): bool
    {
        if (static::isModelOff($modelName)) {
            return true;
        }

        $modelReflection = new ReflectionClass($modelName);

        if ($modelReflection->hasProperty($propertyName)) {
            $propertyReflection = new ReflectionProperty($modelName, $propertyName);
            $attributes = $propertyReflection->getAttributes(Off::class, ReflectionAttribute::IS_INSTANCEOF);
            $explicitlyOff = 0 !== count($attributes);
        } else {
            $explicitlyOff = false;
        }

        return !Config::get('database.auto_infer_migrations') || $explicitlyOff;
    }

    protected static function isMethodOff($modelName, $methodName): bool
    {
        if (static::isModelOff($modelName)) {
            return true;
        }

        $modelReflection = new ReflectionClass($modelName);

        if ($modelReflection->hasMethod($methodName)) {
            $methodReflection = new ReflectionMethod($modelName, $methodName);
            $attributes = $methodReflection->getAttributes(Off::class, ReflectionAttribute::IS_INSTANCEOF);
            $explicitlyOff = 0 !== count($attributes);
        } else {
            $explicitlyOff = false;
        }

        return !Config::get('database.auto_infer_migrations') || $explicitlyOff;
    }

    public static function generateBlueprint(string $modelName): ?Blueprint
    {
        $attributes = static::getMigrationAttributes($modelName);

        foreach ($attributes as $attribute) {
            if ($attribute instanceof Table) {
                $tableAttribute = $attribute;
                break;
            }
        }

        if (!isset($tableAttribute) && static::isModelOff($modelName)) {
            return null;
        }

        /** @var Model */
        $instance = new $modelName();
        $table = new Blueprint($tableAttribute->name ?? $instance->getTable(), null, $tableAttribute->prefix ?? '');

        foreach ($attributes as $attribute) {
            $attribute->applyToBlueprint($table);
        }

        static::inferPrimaryKey($modelName, $table);
        static::inferTimestamps($modelName, $table);
        static::inferSoftDeletes($modelName, $table);

        return $table;
    }

    protected static function inferPrimaryKey(string $modelName, Blueprint $table)
    {
        foreach ($table->getCommands() as $command) {
            if ('primary' === $command->name) {
                return;
            }
        }

        /** @var Model */
        $instance = new $modelName();

        if (static::isPropertyOff($modelName, $instance->getKeyName())) {
            return;
        }

        if ('int' === $instance->getKeyType()) {
            $table->unsignedBigInteger($instance->getKeyName(), $instance->getIncrementing());
        } else {
            $method = Column::TYPE_MAP[$instance->getKeyType()] ?? null;
            if (null === $method) {
                return;
            }

            $table->$method($instance->getKeyName());
        }

        $table->primary($instance->getKeyName());
    }

    protected static function inferTimestamps(string $modelName, Blueprint $table)
    {
        /** @var Model */
        $instance = new $modelName();

        if (!$instance->usesTimestamps()) {
            return;
        }

        $createdAtColumn = $instance->getCreatedAtColumn();
        $updatedAtColumn = $instance->getUpdatedAtColumn();

        foreach ($table->getColumns() as $column) {
            if ($column->name === $createdAtColumn) {
                $createdAtColumn = null;
            }

            if ($column->name === $updatedAtColumn) {
                $updatedAtColumn = null;
            }
        }

        if (null !== $createdAtColumn) {
            $table->timestamp($createdAtColumn)->nullable();
        }

        if (null !== $updatedAtColumn) {
            $table->timestamp($updatedAtColumn)->nullable();
        }
    }

    protected static function inferSoftDeletes(string $modelName, Blueprint $table)
    {
        if (!in_array(SoftDeletes::class, class_uses_recursive($modelName))) {
            return;
        }

        /** @var SoftDeletes */
        $instance = new $modelName();

        $deletedAtColumn = $instance->getDeletedAtColumn();

        foreach ($table->getColumns() as $column) {
            if ($column->name === $deletedAtColumn) {
                return;
            }
        }

        $table->softDeletes($deletedAtColumn);
    }

    public static function getDiff(Blueprint $from, Blueprint $to): BlueprintDiff
    {
        [$modifiedColumns, $droppedColumns, $renamedColumns, $addedColumns] = static::getColumnDiffs($from, $to);
        [$droppedIndexes, $renamedIndexes, $addedIndexes] = static::getIndexDiffs($from, $to);

        return new BlueprintDiff(
            $from,
            $to,
            $modifiedColumns,
            $droppedColumns,
            $renamedColumns,
            $addedColumns,
            $droppedIndexes,
            $renamedIndexes,
            $addedIndexes
        );
    }

    protected static function attributesEqual(Fluent $left, Fluent $right, array $exceptions = [])
    {
        $leftClone = clone $left;
        $rightClone = clone $right;

        $leftAttributes = array_filter($leftClone->getAttributes(), fn ($i) => null !== $i);
        $rightAttributes = array_filter($rightClone->getAttributes(), fn ($i) => null !== $i);

        ksort($leftAttributes);
        ksort($rightAttributes);

        foreach ($exceptions as $exception) {
            unset($leftAttributes[$exception], $rightAttributes[$exception]);
        }

        return $leftAttributes === $rightAttributes;
    }

    /**
     * @param Blueprint $from
     * @param Blueprint $to
     * @return array
     */
    protected static function getColumnDiffs(Blueprint $from, Blueprint $to): array
    {
        $unchangedColumns = [];
        $modifiedColumns = [];
        $droppedColumns = [];
        $addedColumns = [];
        $renamedColumns = [];

        foreach ($from->getColumns() as $fromColumn) {
            foreach ($to->getColumns() as $toColumn) {
                if (in_array($toColumn->name, $unchangedColumns)) {
                    continue;
                }

                if ($fromColumn->name === $toColumn->name) {
                    if (static::attributesEqual($fromColumn, $toColumn)) {
                        unset($renamedColumns[$fromColumn->name]);
                        $unchangedColumns[] = $fromColumn->name;
                        continue 2;
                    }

                    $modifiedColumns[] = $fromColumn->name;
                    continue 2;
                } else {
                    if (static::attributesEqual($fromColumn, $toColumn, ['name'])) {
                        $renamedColumns[$fromColumn->name] = $toColumn->name;
                        continue;
                    }
                }
            }

            if (isset($renamedColumns[$fromColumn->name])) {
                continue;
            }

            $droppedColumns[] = $fromColumn;
        }

        foreach ($to->getColumns() as $toColumn) {
            if (
                in_array($toColumn->name, $unchangedColumns) ||
                in_array($toColumn->name, $modifiedColumns) ||
                in_array($toColumn->name, $renamedColumns)
            ) {
                continue;
            }

            $addedColumns[] = $toColumn;
        }

        return [$modifiedColumns, $droppedColumns, $renamedColumns, $addedColumns];
    }

    /**
     * @param Blueprint $from
     * @param Blueprint $to
     * @return array
     */
    protected static function getIndexDiffs(Blueprint $from, Blueprint $to): array
    {
        $unchangedIndexes = [];
        $droppedIndexes = [];
        $renamedIndexes = [];
        $addedIndexes = [];

        foreach ($from->getCommands() as $fromCommand) {
            if (null === IndexType::tryFrom($fromCommand->name)) {
                continue;
            }

            foreach ($to->getCommands() as $toCommand) {
                if (
                    null === IndexType::tryFrom($toCommand->name) ||
                    in_array($toCommand->index, $unchangedIndexes)
                ) {
                    continue;
                }

                if (static::attributesEqual($fromCommand, $toCommand, ['index'])) {
                    if ($fromCommand->index === $toCommand->index) {
                        unset($renamedIndexes[$fromCommand->index]);
                        $unchangedIndexes[] = $fromCommand->index;
                        continue 2;
                    }

                    $renamedIndexes[$fromCommand->index] = $toCommand->index;
                    continue 2;
                }
            }

            if (isset($renamedIndexes[$fromCommand->index])) {
                continue;
            }

            $droppedIndexes[] = $fromCommand;
        }

        foreach ($to->getCommands() as $toCommand) {
            if (null === IndexType::tryFrom($toCommand->name)) {
                continue;
            }

            if (
                in_array($toCommand->index, $unchangedIndexes) ||
                in_array($toCommand->index, $renamedIndexes)
            ) {
                continue;
            }

            $addedIndexes[] = $toCommand;
        }

        return [$droppedIndexes, $renamedIndexes, $addedIndexes];
    }
}
