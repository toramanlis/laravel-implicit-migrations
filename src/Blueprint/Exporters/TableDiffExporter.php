<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Blueprint\BlueprintDiff;

class TableDiffExporter extends Exporter
{
    public function __construct(protected BlueprintDiff $definition)
    {
    }

    protected function exportUp(): string
    {
        return $this->joinExports([
            $this->exportRename(),
            null,
            $this->exportDroppedColumns(),
            $this->exportModifiedColumns(),
            $this->exportAddedColumns(),
            null,
            $this->exportDroppedIndexes(),
            $this->exportRenamedIndexes(),
            $this->exportAddedIndexes(),
            null,
            $this->exportOptions(),
        ]);
    }

    protected function exportDown(): string
    {
        return $this->joinExports([
            $this->exportRename(true),
            null,
            $this->exportDroppedColumns(true),
            $this->exportModifiedColumns(true),
            $this->exportAddedColumns(true),
            null,
            $this->exportDroppedIndexes(true),
            $this->exportRenamedIndexes(true),
            $this->exportAddedIndexes(true),
            null,
            $this->exportOptions(true),
        ]);
    }

    protected function exportRename(bool $reverse = false): string
    {
        $rename = $this->definition->getRename($reverse);

        if (null === $rename) {
            return '';
        }

        [$oldName, $newName] = $rename;

        return $this->exportMethodCall('rename', [$oldName, $newName]);
    }

    /**
     * @param array<ColumnDefinition> $columns
     * @return string
     */
    protected function exportAddedColumns(bool $reverse = false): string
    {
        $exports = [];

        foreach ($this->definition->getAddedColumns($reverse) as $column) {
            $exports[] = ColumnExporter::exportDefinition($column);
        }

        return $this->joinExports($exports);
    }

    /**
     * @param array<ColumnDefinition> $columns
     * @return string
     */
    protected function exportDroppedColumns(bool $reverse = false): string
    {
        $exports = [];

        foreach ($this->definition->getDroppedColumns($reverse) as $column) {
            $exports[] = ColumnExporter::exportDefinition($column, ColumnExporter::MODE_DOWN);
        }

        return $this->joinExports($exports);
    }

    /**
     * @param array<string> $columns
     * @return string
     */
    protected function exportModifiedColumns(bool $reverse = false): string
    {
        $exports = [];

        foreach ($this->definition->getModifiedColumns($reverse) as $column) {
            $exports[] = ColumnDiffExporter::exportDefinition($column);
        }

        return $this->joinExports($exports);
    }

    /**
     * @param array<Fluent> $indexes
     * @return string
     */
    protected function exportDroppedIndexes(bool $reverse = false): string
    {
        $exports = [];

        foreach ($this->definition->getDroppedIndexes($reverse) as $index) {
            $exports[] = IndexExporter::exportDefinition($index, IndexExporter::MODE_DOWN);
        }

        return $this->joinExports($exports);
    }

    /**
     * @param array<Fluent> $indexes
     * @return string
     */
    protected function exportAddedIndexes(bool $reverse = false): string
    {
        $exports = [];

        foreach ($this->definition->getAddedIndexes($reverse) as $index) {
            $exports[] = IndexExporter::exportDefinition($index);
        }

        return $this->joinExports($exports);
    }

    protected function exportRenamedIndexes(bool $reverse = false): string
    {
        $exports = [];

        foreach ($this->definition->getRenamedIndexes($reverse) as $from => $to) {
            $exports[] = IndexExporter::renameIndex($from, $to);
        }

        return $this->joinExports($exports);
    }

    protected function exportOptions(bool $reverse = false): string
    {
        $exports = [];

        foreach (['engine', 'charset', 'collation'] as $optionName) {
            $optionChange = $this->definition->getOptionChange($optionName, $reverse);
            if (null === $optionChange) {
                continue;
            }

            $exports[] = $this->exportMethodCall($optionName, [$optionChange]);
        }

        return $this->joinExports($exports);
    }
}
