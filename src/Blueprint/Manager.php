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
use Toramanlis\ImplicitMigrations\Attributes\ForeignKey;
use Toramanlis\ImplicitMigrations\Attributes\Index;
use Toramanlis\ImplicitMigrations\Attributes\MigrationAttribute;
use Toramanlis\ImplicitMigrations\Attributes\Off;
use Toramanlis\ImplicitMigrations\Attributes\PivotColumn;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
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
        /** @var array<SimplifyingBlueprint> */
        protected array $blueprints
    ) {
    }

    public static function makeBlueprint(string $tableName, $prefix = ''): SimplifyingBlueprint
    {
        return new SimplifyingBlueprint($prefix . $tableName);
    }

    /** @return array<SimplifyingBlueprint>  */
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
     * @return array<string,SimplifyingBlueprint>
     */
    public static function mergeMigrationsToBlueprints(array $migrations): array
    {
        $blueprints = [];
        foreach ($migrations as $migration) {
            $blueprints[$migration->getSource()] = $blueprints[$migration->getSource()]
                ?? new SimplifyingBlueprint($migration->getTableNameNew());

            $blueprint = $blueprints[$migration->getSource()];
            $migration->tableUp($blueprint);
            $blueprint->separateIndexesFromColumns();
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
            $blueprint = static::makeBlueprint($table);
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
        string $localKey,
        string $foreignKeyAlias
    ) {
        $blueprint = $this->getBlueprintByTable($relatedTable);

        foreach ($blueprint->getCommands() as $command) {
            if ('foreign' !== $command->name) {
                continue;
            }

            if (
                $command->columns[0] === $foreignKey &&
                $command->references === $localKey &&
                $command->on === $parentTable
            ) {
                return;
            }
        }

        $parentBlueprint = $this->getBlueprintByTable($parentTable);

        static::ensureKeyColumn($parentBlueprint, $localKey);

        $index = $blueprint->foreign($foreignKey)
            ->references($localKey)
            ->on($parentTable);

        $index->index = str_replace($foreignKey, $foreignKeyAlias, $index->index);

        return $index;
    }

    protected function applyDirectRelationshipToBlueprints(DirectRelationship $relationship)
    {
        $blueprint = $this->getBlueprintByTable($relationship->getRelatedTable());
        $this->relationshipMap[$relationship->getRelatedTable()] = $relationship;

        static::ensureKeyColumn($blueprint, $relationship->getForeignKey(), 'unsignedBigInteger');

        if (in_array(Polymorphic::class, class_uses_recursive($relationship))) {
            /** @var Polymorphic $relationship */
            $this->ensureKeyColumn($blueprint, $relationship->getTypeKey(), 'string');
        }

        $this->relationshipMap[$relationship->getRelatedTable()] = $relationship;
        $this->relationshipMap[$relationship->getParentTable()] = $relationship;

        $this->defineForeignKey(
            $relationship->getRelatedTable(),
            $relationship->getForeignKey(),
            $relationship->getParentTable(),
            $relationship->getLocalKey(),
            $relationship->getForeignKeyAlias()
        );
    }

    protected function applyIndirectRelationshipToBlueprints(IndirectRelationship $relationship)
    {
        $targetBlueprint = $this
                ->getBlueprintByTable($relationship->getRelatedTables()[0]);
        $blueprint = $this
            ->getBlueprintByTable($relationship->getRelatedTables()[1]);
        $pivotBlueprint = $this
            ->getBlueprintByTable($relationship->getPivotTable());

        [
            $targetBlueprint->getTable() => $targetForeignKey,
            $blueprint->getTable() => $foreignKey
        ] = $relationship->getForeignKeys();
        [
            $targetBlueprint->getTable() => $targetForeignKeyAlias,
            $blueprint->getTable() => $foreignKeyAlias
        ] = $relationship->getForeignKeyAliases();
        [
            $targetBlueprint->getTable() => $targetLocalKey,
            $blueprint->getTable() => $localKey
        ] = $relationship->getLocalKeys();

        $this->relationshipMap[$blueprint->getTable()] = $relationship;
        $this->relationshipMap[$pivotBlueprint->getTable()] = $relationship;

        $this->ensureKeyColumn($pivotBlueprint, $foreignKey, 'unsignedBigInteger');
        $this->ensureKeyColumn($pivotBlueprint, $targetForeignKey, 'unsignedBigInteger');
        $this->defineForeignKey(
            $relationship->getPivotTable(),
            $foreignKey,
            $blueprint->getTable(),
            $localKey,
            $foreignKeyAlias
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
        }

        $this->relationshipMap[$targetBlueprint->getTable()] = $relationship;
        $this->ensureKeyColumn($targetBlueprint, $targetLocalKey);

        $this->defineForeignKey(
            $relationship->getPivotTable(),
            $targetForeignKey,
            $targetBlueprint->getTable(),
            $targetLocalKey,
            $targetForeignKeyAlias
        );
    }

    protected function applyRelationshipToBlueprints(RelationshipsRelationship $relationship)
    {
        if (!$relationship->isReady()) {
            return;
        }

        if ($relationship instanceof DirectRelationship) {
            $this->applyDirectRelationshipToBlueprints($relationship);
        } elseif ($relationship instanceof IndirectRelationship) {
            $this->applyIndirectRelationshipToBlueprints($relationship);
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

        if (property_exists(Model::class, $propertyName)) {
            return true;
        }

        $modelReflection = new ReflectionClass($modelName);

        if ($modelReflection->hasProperty($propertyName)) {
            $propertyReflection = new ReflectionProperty($modelName, $propertyName);
            $attributes = $propertyReflection->getAttributes(Off::class, ReflectionAttribute::IS_INSTANCEOF);
            $explicitlyOff = 0 !== count($attributes);
        } else {
            $explicitlyOff = false; // @codeCoverageIgnore
        }

        return !Config::get('database.auto_infer_migrations') || $explicitlyOff;
    }

    protected static function isMethodOff($modelName, $methodName): bool
    {
        if (static::isModelOff($modelName)) {
            return true;
        }

        if (method_exists(Model::class, $methodName)) {
            return true;
        }

        $modelReflection = new ReflectionClass($modelName);

        if ($modelReflection->hasMethod($methodName)) {
            $methodReflection = new ReflectionMethod($modelName, $methodName);
            $attributes = $methodReflection->getAttributes(Off::class, ReflectionAttribute::IS_INSTANCEOF);
            $explicitlyOff = 0 !== count($attributes);
        } else {
            $explicitlyOff = false; // @codeCoverageIgnore
        }

        return !Config::get('database.auto_infer_migrations') || $explicitlyOff;
    }

    public static function generateBlueprint(string $modelName): ?SimplifyingBlueprint
    {
        $attributes = static::getMigrationAttributes($modelName);

        if (empty($attributes) && static::isModelOff($modelName)) {
            return null; // @codeCoverageIgnore
        }

        /** @var Model */
        $instance = new $modelName();
        $table = static::makeBlueprint($tableAttribute->name ?? $instance->getTable(), $tableAttribute->prefix ?? '');

        foreach ($attributes as $attribute) {
            /** @var AppliesToBlueprint $attribute */
            $attribute->applyToBlueprint($table);
        }

        static::inferPrimaryKey($modelName, $table);
        static::inferTimestamps($modelName, $table);
        static::inferSoftDeletes($modelName, $table);

        return $table;
    }

    public function ensureIndexColumns(array $modelNames): void
    {
        foreach ($modelNames as $modelName) {
            $table = $this->blueprints[(new $modelName())->getTable()] ?? null;

            if (!$table) {
                continue; // @codeCoverageIgnore
            }

            $attributes = static::getMigrationAttributes($modelName);
            foreach ($attributes as $attribute) {
                if ($attribute instanceof ForeignKey) {
                    $attribute->ensureColumns($table, $this->blueprints, $modelNames);
                } elseif ($attribute instanceof Index) {
                    $attribute->ensureColumns($table);
                }
            }
        }
    }

    protected static function inferPrimaryKey(string $modelName, Blueprint $table)
    {
        /** @var Model */
        $instance = new $modelName();

        $columnExists = array_reduce(
            $table->getColumns(),
            fn ($carry, $column) => $carry || $column->name === $instance->getKeyName(),
            false
        );

        foreach ($table->getCommands() as $command) {
            if ('primary' !== $command->name) {
                continue;
            }

            if (
                count($command->columns) === 1 &&
                $instance->getKeyName() === $command->columns[0] &&
                $columnExists
            ) {
                return; // @codeCoverageIgnore
            }

            break;
        }

        if (!$columnExists) {
            if ('int' === $instance->getKeyType()) {
                if ($instance->getIncrementing()) {
                    $table->id($instance->getKeyName());
                } else {
                    // @codeCoverageIgnoreStart
                    $table->unsignedBigInteger($instance->getKeyName(), $instance->getIncrementing());
                }
            } else {
                $method = Column::TYPE_MAP[$instance->getKeyType()] ?? null;
                if (null === $method) {
                    return;
                }

                $table->$method($instance->getKeyName());
                // @codeCoverageIgnoreEnd
            }
        }

        $table->primary($instance->getKeyName()); // @codeCoverageIgnore
    }

    protected static function inferTimestamps(string $modelName, Blueprint $table)
    {
        /** @var Model */
        $instance = new $modelName();

        if (!$instance->usesTimestamps()) {
            return; // @codeCoverageIgnore
        }

        $createdAtColumn = $instance->getCreatedAtColumn();
        $updatedAtColumn = $instance->getUpdatedAtColumn();

        foreach ($table->getColumns() as $column) {
            if ($column->name === $createdAtColumn) {
                $createdAtColumn = null; // @codeCoverageIgnore
            }

            if ($column->name === $updatedAtColumn) {
                $updatedAtColumn = null; // @codeCoverageIgnore
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
                return; // @codeCoverageIgnore
            }
        }

        $table->softDeletes($deletedAtColumn);
    }

    public static function getDiff(SimplifyingBlueprint $from, SimplifyingBlueprint $to): BlueprintDiff
    {
        [$modifiedColumns, $droppedColumns, $addedColumns] = static::getColumnDiffs($from, $to);
        [$droppedIndexes, $renamedIndexes, $addedIndexes] = static::getIndexDiffs($from, $to);

        return new BlueprintDiff(
            $from,
            $to,
            $modifiedColumns,
            $droppedColumns,
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

        foreach ($from->getColumns() as $fromColumn) {
            foreach ($to->getColumns() as $toColumn) {
                if (in_array($toColumn->name, $unchangedColumns)) {
                    continue;
                }

                if ($fromColumn->name === $toColumn->name) {
                    if (static::attributesEqual($fromColumn, $toColumn)) {
                        $unchangedColumns[] = $fromColumn->name;
                        continue 2;
                    }

                    $modifiedColumns[] = $fromColumn->name;
                    continue 2;
                }
            }

            $droppedColumns[] = $fromColumn;
        }

        foreach ($to->getColumns() as $toColumn) {
            if (
                in_array($toColumn->name, $unchangedColumns) ||
                in_array($toColumn->name, $modifiedColumns)
            ) {
                continue;
            }

            $addedColumns[] = $toColumn;
        }

        return [$modifiedColumns, $droppedColumns, $addedColumns];
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
                continue; // @codeCoverageIgnore
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
