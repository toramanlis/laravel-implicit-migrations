<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Exception;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Attributes\IndexType;

class BlueprintDiff implements Migratable
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
}
