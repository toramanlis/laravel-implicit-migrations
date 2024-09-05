<?php

namespace Toramanlis\ImplicitMigrations\Exporters;

use Doctrine\DBAL\Schema\TableDiff;

class TableDiffExporter extends AbstractAssetExporter
{
    public function __construct(
        protected TableDiff $asset
    ) {
    }

    public function exportCreate(): string
    {
        return static::joinExports([
            $this->exportColumns(),
            $this->exportIndexes(),
            $this->exportForeignKeys(),
        ]);
    }

    public function exportDrop(): string
    {
        return '';
    }

    /** @return array<string>  */
    protected function exportColumns(): array
    {
        $columnChanges = [];

        foreach ($this->asset->getDroppedColumns() as $droppedColumn) {
            $columnChanges[] = ColumnExporter::exportAsset($droppedColumn, ColumnExporter::MODE_DROP);
        }

        foreach ($this->asset->getChangedColumns() as $changedColumn) {
            $columnChanges[] = ColumnDiffExporter::exportAsset($changedColumn);
        }

        foreach ($this->asset->getAddedColumns() as $addedColumn) {
            $columnChanges[] = ColumnExporter::exportAsset($addedColumn);
        }

        return $columnChanges;
    }

    protected function exportIndexes()
    {
        $indexChanges = [];

        foreach ($this->asset->getDroppedIndexes() as $droppedIndex) {
            $indexChanges[] = IndexExporter::exportAsset($droppedIndex, IndexExporter::MODE_DROP);
        }

        foreach ($this->asset->getRenamedIndexes() as $oldName => $renamedIndex) {
            $indexChanges[] = IndexExporter::exportRenameIndex($oldName, $renamedIndex);
        }

        foreach ($this->asset->getModifiedIndexes() as $modifiedIndex) {
            $indexChanges[] = IndexExporter::exportAsset($modifiedIndex, IndexExporter::MODE_DROP);
            $indexChanges[] = IndexExporter::exportAsset($modifiedIndex);
        }

        foreach ($this->asset->getAddedIndexes() as $addedIndex) {
            $indexChanges[] = IndexExporter::exportAsset($addedIndex);
        }

        return $indexChanges;
    }

    protected function exportForeignKeys()
    {
        $foreignKeyChanges = [];

        foreach ($this->asset->getDroppedForeignKeys() as $droppedForeignKey) {
            $foreignKeyChanges[] = ForeignKeyConstraintExporter::exportAsset(
                $droppedForeignKey,
                ForeignKeyConstraintExporter::MODE_DROP
            );
        }

        foreach ($this->asset->getModifiedForeignKeys() as $modifiedForeignKey) {
            $foreignKeyChanges[] = ForeignKeyConstraintExporter::exportAsset(
                $modifiedForeignKey,
                ForeignKeyConstraintExporter::MODE_DROP
            );
            $foreignKeyChanges[] = ForeignKeyConstraintExporter::exportAsset($modifiedForeignKey);
        }

        foreach ($this->asset->getAddedForeignKeys() as $addedForeignKey) {
            $foreignKeyChanges[] = ForeignKeyConstraintExporter::exportAsset($addedForeignKey);
        }

        return $foreignKeyChanges;
    }
}
