<?php

namespace Toramanlis\ImplicitMigrations\Exporters;

use Doctrine\DBAL\Schema\UniqueConstraint;

class UniqueConstraintExporter extends AbstractAssetExporter
{
    public function __construct(
        protected UniqueConstraint $asset
    ) {
    }

    public function exportCreate(): string
    {
        return static::exportMethodCall('addUniqueConstraint', [
            $this->asset->getColumns(),
            $this->asset->getName(),
            $this->asset->getFlags(),
            $this->asset->getOptions(),
        ]);
    }

    public function exportDrop(): string
    {
        return static::exportMethodCall('removeUniqueConstraint', [$this->asset->getName()]);
    }
}
