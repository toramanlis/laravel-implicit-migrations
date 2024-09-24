<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Fluent;

class BlueprintDiff
{
    /**
     * @param array<string> $modifiedColumns
     * @param array<ColumnDefinition> $droppedColumns
     * @param array<string, string> $renamedColumns
     * @param array<ColumnDefinition> $addedColumns
     * @param array<Fluent> $droppedIndexes
     * @param array<string, string> $renamedIndexes
     * @param array<Fluent> $addedIndexes
     */
    public function __construct(
        readonly public Blueprint $from,
        readonly public Blueprint $to,
        readonly public array $modifiedColumns,
        readonly public array $droppedColumns,
        readonly public array $renamedColumns,
        readonly public array $addedColumns,
        readonly public array $droppedIndexes,
        readonly public array $renamedIndexes,
        readonly public array $addedIndexes
    ) {
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
            null === $this->getCollationChange();
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

    public function getRenamedColumns(bool $reverse = false)
    {
        $renames = $this->renamedColumns;
        return $reverse ? array_flip($renames) : $renames;
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
        $droppedIndexes = $reverse ? $this->addedIndexes : $this->droppedIndexes;
        $droppedColumnNames = array_map(fn ($i) => $i->name, $this->getDroppedColumns($reverse));

        foreach ($droppedIndexes as $i => $index) {
            foreach ($index->columns as $columnName) {
                if (!in_array($columnName, $droppedColumnNames)) {
                    continue 2;
                }
            }

            unset($droppedIndexes[$i]);
        }

        return $droppedIndexes;
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
