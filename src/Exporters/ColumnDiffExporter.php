<?php

namespace Toramanlis\ImplicitMigrations\Exporters;

use Doctrine\DBAL\Schema\ColumnDiff;

class ColumnDiffExporter extends AbstractAssetExporter
{
    public function __construct(
        protected ColumnDiff $asset
    ) {
    }

    public function exportOption(string $optionName): string
    {
        if (!$this->asset->{"has{$optionName}Changed"}()) {
            return '';
        }

        if ('Name' === $optionName) {
            return '$table->renameColumn(' . static::exportParameters([
                $this->asset->getOldColumn()->getName(),
                $this->asset->getNewColumn()->getName(),
            ]) . ');';
        }

        $getter = "get{$optionName}";
        $setter = "set{$optionName}";

        return "\$table->getColumn(" . static::varExport($this->asset->getOldColumn()->getName()) . ")\n"
            . "\t->{$setter}(" . static::varExport($this->asset->getNewColumn()->{"{$getter}"}()) . ");";
    }

    public function exportCreate(): string
    {
        $optionNames = [
            'Unsigned', 'Autoincrement', 'Default', 'Fixed', 'Precision',
            'Scale', 'Length', 'Notnull', 'Name', 'Type', 'Comment'
        ];


        $optionChanges = [];

        foreach ($optionNames as $optionName) {
            $optionChanges[] = $this->exportOption($optionName);
        }

        return static::joinExports($optionChanges);
    }

    public function exportDrop(): string
    {
        return '';
    }
}
