<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use ReflectionAttribute;
use ReflectionClass;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\MigrationAttribute;
use Toramanlis\ImplicitMigrations\Attributes\Table;

class Manager
{
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
     * @param array<ImplicitMigration> $existingMigrations
     * @return array<string, Blueprint>
     */
    public static function mergeMigrationsToBlueprints(array $migrations): array
    {
        $blueprints = [];
        foreach ($migrations as $migration) {
            $source = $migration->getSource();

            $blueprints[$source] = $blueprints[$source] ??
                new Blueprint($migration->getTableNameNew());

            $migration->tableUp($blueprints[$source]);
        }

        return $blueprints;
    }

    /**
     * @param string $modelName
     * @return array<Blueprint>
     */
    public static function generateBlueprints(string $modelName): array
    {
        $attributes = static::getMigrationAttributes($modelName);

        foreach ($attributes as $attribute) {
            if ($attribute instanceof Table) {
                $tableAttribute = $attribute;
                break;
            }
        }

        if (!isset($tableAttribute)) {
            return [];
        }

        $table = new Blueprint($tableAttribute->name, null, $tableAttribute->prefix);

        foreach ($attributes as $attribute) {
            $attribute->applyToBlueprint($table);
        }

        static::inferPrimaryKey($modelName, $table);
        static::inferTimestamps($modelName, $table);
        static::inferSoftDeletes($modelName, $table);

        return [$modelName => $table];
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
