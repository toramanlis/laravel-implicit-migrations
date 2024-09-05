<?php

namespace Toramanlis\ImplicitMigrations\Exporters;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;

class ForeignKeyConstraintExporter extends AbstractAssetExporter
{
    public function __construct(
        protected ForeignKeyConstraint $asset
    ) {
    }

    public function exportCreate(): string
    {
        return static::exportMethodCall('addForeignKeyConstraint', [
            $this->asset->getForeignTableName(),
            $this->asset->getLocalColumns(),
            $this->asset->getForeignColumns(),
            $this->asset->getOptions(),
            $this->asset->getName(),
        ]);
    }
    public function exportDrop(): string
    {
        return static::exportMethodCall('removeForeignKey', [$this->asset->getName()]);
    }
}
