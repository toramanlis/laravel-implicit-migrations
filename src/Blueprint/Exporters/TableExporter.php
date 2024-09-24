<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\Exporter;
use Toramanlis\ImplicitMigrations\Blueprint\IndexType;

class TableExporter extends Exporter
{
    public function __construct(protected Blueprint $definition)
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

        $softDeletes = null;
        foreach ($this->definition->getColumns() as $column) {
            if ($hasTimestamps && in_array($column->name, ['created_at', 'updated_at'])) {
                continue;
            }

            $exporter = new ColumnExporter($column);
            $columnExport = $exporter->export();

            if ('softDeletes' === $exporter->getCollapsedType()) {
                $softDeletes = $columnExport;
                continue;
            }

            
            if ('id' === $exporter->getCollapsedType()) {
                array_unshift($columnExports, [$columnExport]);
            } else {
                $columnExports[] = $columnExport;
            }
        }

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
        $state = $this->definition->getState();

        $indexExports = [];

        foreach ($this->definition->getCommands() as $command) {
            if (!$command instanceof Fluent) {
                continue;
            }

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
