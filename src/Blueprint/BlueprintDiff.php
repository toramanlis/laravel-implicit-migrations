<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Fluent;

class BlueprintDiff
{
    /**
     * @param SimplifyingBlueprint $from
     * @param SimplifyingBlueprint $to
     * @param array<string> $modifiedColumns
     * @param array<ColumnDefinition> $droppedColumns
     * @param array<ColumnDefinition> $addedColumns
     * @param array<Fluent> $droppedIndexes
     * @param array<string, string> $renamedIndexes
     * @param array<Fluent> $addedIndexes
     */
    public function __construct(
        readonly public SimplifyingBlueprint $from,
        readonly public SimplifyingBlueprint $to,
        public array $modifiedColumns,
        public array $droppedColumns,
        public array $addedColumns,
        public array $droppedIndexes,
        public array $renamedIndexes,
        public array $addedIndexes
    ) {
        $this->applyColumnIndexes();
        $this->applyColumnIndexes(true);
    }

    protected function applyColumnIndexes(bool $reverse = false)
    {
        $blueprint = new SimplifyingBlueprint($this->to->getTable());

        foreach ($this->getAddedColumns($reverse) as $column) {
            $blueprint->addColumn($column->type, $column->name, $column->getAttributes());
        }

        foreach ($this->getAddedIndexes($reverse) as $index) {
            $blueprint->{$index->name}($index->columns, $index->index, $index->algorithm);
        }

        foreach ($this->getRenamedIndexes($reverse) as $from => $to) {
            $blueprint->renameIndex($from, $to);
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
}
