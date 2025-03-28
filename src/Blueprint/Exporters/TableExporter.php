<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\Exporter;
use Toramanlis\ImplicitMigrations\Blueprint\IndexType;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;

class TableExporter extends Exporter
{
    public function __construct(protected SimplifyingBlueprint $definition)
    {
    }

    protected function exportUp(): string
    {
        return $this->joinExports([
            $this->exportColumns(),
            null,
            $this->exportIndexes(),
            null,
            $this->exportTableOptions(),
        ]);
    }

    protected function exportDown(): string
    {
        return $this->exportMethodCall('drop');
    }

    protected function exportColumns(): string
    {
        $columnExports = [];
        $hasTimestamps = false;

        $precisions = [
            'createdAt' => null,
            'updatedAt' => null,
        ];

        foreach ($this->definition->getColumns() as $column) {
            if ('timestamp' !== $column->type || !$column->nullable) {
                continue;
            }

            if ('created_at' === $column->name) {
                $precisions['createdAt'] = $column->precision;
            } elseif ('updated_at' === $column->name) {
                $precisions['updatedAt'] = $column->precision;
            }

            if (null !== $precisions['createdAt'] && $precisions['createdAt'] === $precisions['updatedAt']) {
                $hasTimestamps = true;
                $precision = $precisions['createdAt'];
                break;
            }
        }

        $this->definition->applyColumnIndexes();

        $softDeletes = null;
        $ids = [];
        foreach ($this->definition->getColumns() as $column) {
            if ($hasTimestamps && in_array($column->name, ['created_at', 'updated_at'])) {
                continue;
            }

            $exporter = App::make(ColumnExporter::class, ['definition' => $column]);
            $columnExport = $exporter->export();

            if ('softDeletes' === $exporter->getCollapsedType()) {
                $softDeletes = $columnExport;
                continue;
            }

            if ('id' === $exporter->getCollapsedType()) {
                if ('id' == $column->name) {
                    array_unshift($ids, $columnExport);
                } else {
                    $ids[] = $columnExport;
                }
            } else {
                $columnExports[] = $columnExport;
            }
        }

        array_unshift($columnExports, ...$ids);

        if ($hasTimestamps) {
            $columnExports[] = static::exportMethodCall('timestamps', $precision ? [$precision] : []);
        }

        if (null !== $softDeletes) {
            $columnExports[] = $softDeletes;
        }

        return $this->joinExports($columnExports);
    }

    protected function exportIndexes(): string
    {
        $indexExports = [];

        foreach ($this->definition->getCommands() as $command) {
            $type = IndexType::tryFrom(strtolower($command->name));

            if (null === $type) {
                continue;
            }

            $indexFluent = clone $command;
            $indexFluent->name = lcfirst($type->name);

            $indexExport = IndexExporter::exportDefinition($indexFluent);

            if (IndexType::Primary === $type) {
                array_unshift($indexExports, $indexExport);
            } else {
                $indexExports[] = $indexExport;
            }
        }

        return $this->joinExports($indexExports);
    }

    protected function exportTableOptions()
    {
        $optionExports = [];

        foreach (['engine', 'charset', 'collation'] as $option) {
            if (null === $this->definition->$option) {
                continue;
            }

            $optionExports[] = $this->exportMethodCall($option, [$this->definition->$option]);
        }

        return $this->joinExports($optionExports);
    }
}
