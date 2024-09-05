<?php

namespace Toramanlis\ImplicitMigrations\Exporters;

use Doctrine\DBAL\Schema\Table;

class TableExporter extends AbstractAssetExporter
{
    public function __construct(
        protected Table $asset
    ) {
    }

    public function exportCreate(): string
    {
        return static::joinExports([
            $this->exportColumns(),
            $this->exportIndexes(),
            $this->exportUniqueConstraints(),
            $this->exportForeignKeyConstraints(),
            $this->exportOptions(),
        ]);
    }

    public function exportDrop(): string
    {
        return "\$table->drop();";
    }

    /** @return array<string>  */
    protected function exportColumns(): array
    {
        $columnDefinitions = [];
        foreach ($this->asset->getColumns() as $column) {
            $columnDefinitions[] = ColumnExporter::exportAsset($column);
        }

        return $columnDefinitions;
    }

    /** @return array<string>  */
    protected function exportIndexes(): array
    {
        $indexDefinitions = [];
        foreach ($this->asset->getIndexes() as $index) {
            $indexDefinitions[] = IndexExporter::exportAsset($index);
        }

        return $indexDefinitions;
    }

    /** @return array<string>  */
    protected function exportUniqueConstraints(): array
    {
        $constraintDefinitions = [];
        foreach ($this->asset->getUniqueConstraints() as $uniqueConstraint) {
            $constraintDefinitions[] = UniqueConstraintExporter::exportAsset($uniqueConstraint);
        }

        return $constraintDefinitions;
    }

    /** @return array<string>  */
    protected function exportForeignKeyConstraints(): array
    {
        $constraintDefinitions = [];
        foreach ($this->asset->getForeignKeys() as $foreignKeyConstraint) {
            $constraintDefinitions[] = ForeignKeyConstraintExporter::exportAsset($foreignKeyConstraint);
        }

        return $constraintDefinitions;
    }

    /** @return array<string>  */
    protected function exportOptions(): array
    {
        $defaultTable = new Table('not_' . $this->asset->getName());

        $optionDefinitions = [];
        foreach ($this->asset->getOptions() as $key => $value) {
            $defaultValue = $defaultTable->getOption($key);

            if ($value === $defaultValue) {
                continue;
            }

            $optionDefinitions[] = static::exportMethodCall('addOption', [$key, $value]);
        }

        return $optionDefinitions;
    }
}
