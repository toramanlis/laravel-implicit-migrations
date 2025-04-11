<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Exception;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Attributes\IndexType;

class BlueprintDiff implements Migratable
{
    /**
     * @param array<string>
     */
    public array $modifiedColumns;

    /**
     * @param array<ColumnDefinition> $droppedColumns
     */
    public array $droppedColumns;

    /**
     * @param array<ColumnDefinition> $addedColumns
     */
    public array $addedColumns;

    /**
     * @param array<Fluent> $droppedIndexes
     */
    public array $droppedIndexes;

    /**
     * @param array<string, string> $renamedIndexes
     */
    public array $renamedIndexes;

    /**
     * @param array<Fluent> $addedIndexes
     */
    public array $addedIndexes;

    /**
     * @param SimplifyingBlueprint $from
     * @param SimplifyingBlueprint $to
     */
    public function __construct(
        readonly public SimplifyingBlueprint $from,
        readonly public SimplifyingBlueprint $to
    ) {
        [$this->modifiedColumns, $this->droppedColumns, $this->addedColumns] = static::getColumnDiffs($from, $to);
        [$this->droppedIndexes, $this->renamedIndexes, $this->addedIndexes] = static::getIndexDiffs($from, $to);
    }

    public function applyColumnIndexes(bool $reverse = false)
    {
        $original = $reverse ? $this->from : $this->to;

        /** @var SimplifyingBlueprint */
        $blueprint = App::make(SimplifyingBlueprint::class, ['tableName' => ($original)->getTable()]);

        foreach ($this->getAddedColumns($reverse) as $column) {
            $blueprint->addColumn($column->type, $column->name, $column->getAttributes());
        }

        foreach ($this->getAddedIndexes($reverse) as $index) {
            $addedIndex = $blueprint->{$index->name}($index->columns, $this->indexName($index), $index->algorithm);

            foreach (array_keys($index->getAttributes()) as $attribute) {
                $addedIndex->{$attribute} = $index->{$attribute};
            }
        }

        $blueprint->applyColumnIndexes();

        if ($reverse) {
            $this->droppedColumns = $blueprint->getColumns();
            $this->droppedIndexes = [];
            $addedIndexes = &$this->droppedIndexes;
        } else {
            $this->addedColumns = $blueprint->getColumns();
            $this->addedIndexes = [];
            $addedIndexes = &$this->addedIndexes;
        }

        foreach ($blueprint->getCommands() as $command) {
            if (null === IndexType::tryFrom($command->name)) {
                continue;
            }

            $addedIndexes[] = $command;
        }
    }

    public function none()
    {
        return empty($this->modifiedColumns) &&
            empty($this->droppedColumns) &&
            empty($this->renamedColumns) &&
            empty($this->addedColumns) &&
            empty($this->droppedIndexes) &&
            empty($this->renamedIndexes) &&
            empty($this->addedIndexes) &&
            null === $this->getEngineChange() &&
            null === $this->getCharsetChange() &&
            null === $this->getCollationChange() &&
            null === $this->getRename();
    }

    public function getAddedColumns(bool $reverse = false)
    {
        return $reverse ? $this->droppedColumns : $this->addedColumns;
    }

    public function getDroppedColumns(bool $reverse = false)
    {
        return $reverse ? $this->addedColumns : $this->droppedColumns;
    }

    public function getModifiedColumns(bool $reverse = false): array
    {
        $reference = $reverse ? $this->from : $this->to;

        $modifiedColumns = [];

        foreach ($reference->getColumns() as $column) {
            if (!in_array($column->name, $this->modifiedColumns)) {
                continue;
            }

            $modifiedColumns[] = $column;
        }

        return $modifiedColumns;
    }

    public function getRenamedIndexes(bool $reverse = false)
    {
        $renames = $this->renamedIndexes;
        return $reverse ? array_flip($renames) : $renames;
    }

    public function getAddedIndexes(bool $reverse = false)
    {
        return $reverse ? $this->droppedIndexes : $this->addedIndexes;
    }

    public function getDroppedIndexes(bool $reverse = false)
    {
        return $reverse ? $this->addedIndexes : $this->droppedIndexes;
    }

    public function getOptionChange(string $optionName, bool $reverse = false): ?string
    {
        if ($this->from->$optionName === $this->to->$optionName) {
            return null;
        }

        return $reverse ? $this->from->$optionName : $this->to->$optionName;
    }

    public function getEngineChange(bool $reverse = false)
    {
        return $this->getOptionChange('engine', $reverse);
    }

    public function getCharsetChange(bool $reverse = false)
    {
        return $this->getOptionChange('charset', $reverse);
    }

    public function getCollationChange(bool $reverse = false)
    {
        return $this->getOptionChange('collation', $reverse);
    }

    public function getRename(bool $reverse = false)
    {
        if ($this->from->getTable() === $this->to->getTable()) {
            return null;
        }

        $rename = [$this->from->getTable(), $this->to->getTable()];
        return $reverse ? array_reverse($rename) : $rename;
    }

    public function getDependedColumnNames(): array
    {
        $dependedColumns = [];
        foreach ($this->addedIndexes as $index) {
            if (IndexType::Foreign->value !== $index->name) {
                continue;
            }

            $references = is_array($index->references) ? $index->references : [$index->references];
            foreach ($references as $reference) {
                $dependedColumns[] = "{$index->on}.{$reference}";
            }
        }

        return $dependedColumns;
    }

    public function getAddedColumnNames(): array
    {
        return array_map(fn ($column) => $column->name, $this->addedColumns);
    }

    public function extractForeignKey(string $on, string $reference): Fluent
    {
        foreach ($this->addedIndexes as $index) {
            $references = is_array($index->references) ? $index->references : [$index->references];
            if (
                IndexType::Foreign->value !== $index->name ||
                $index->on !== $on ||
                !in_array($reference, $references)
            ) {
                continue;
            }

            $this->addedIndexes = array_filter(
                $this->addedIndexes,
                fn($i) => $this->indexName($i) !== $this->indexName($index)
            );
            return $index;
        }

        throw new Exception("Reference {$on}.{$reference} has no foreign key in blueprint for {$this->to->getTable()}");
    }

    public function dropAddedIndex($indexName, bool $reverse = false)
    {
        $remaining = [];

        foreach ($this->getAddedIndexes($reverse) as $index) {
            if ($this->indexName($index) === $indexName) {
                continue;
            }

            $remaining[] = $index;
        }

        if ($reverse) {
            $this->droppedIndexes = $remaining;
        } else {
            $this->addedIndexes = $remaining;
        }
    }

    public function stripDefaultIndexNames(bool $reverse = false)
    {
        $blueprint = $reverse ? $this->from : $this->to;
        foreach ($this->getAddedIndexes($reverse) as $index) {
            if ($blueprint->defaultIndexName($index) === $index->index) {
                $index->index = null;
            }
        }
    }

    public function indexName(Fluent $index, bool $reverse = false)
    {
        return $index->index ?? $this->defaultIndexName($index, $reverse);
    }

    public function defaultIndexName(Fluent $index, bool $reverse = false)
    {
        return ($reverse ? $this->from : $this->to)->defaultIndexName($index);
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
