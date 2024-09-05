<?php

namespace Toramanlis\ImplicitMigrations\Exporters;

use Doctrine\DBAL\Schema\Index;

class IndexExporter extends AbstractAssetExporter
{
    public function __construct(
        protected Index $asset
    ) {
    }

    public function exportCreate(): string
    {
        $parameters = [
            $this->asset->getColumns(),
            $this->asset->getName(),
        ];

        if ($this->asset->isPrimary()) {
            $setter = 'setPrimaryKey';
        } else {
            if ($this->asset->isUnique()) {
                $setter = 'addUniqueIndex';
            } else {
                $setter = 'addIndex';
                $parameters[] = $this->asset->getFlags();
            }

            $parameters[] = $this->asset->getOptions();
        }

        return static::exportMethodCall($setter, $parameters);
    }

    public function exportDrop(): string
    {
        return static::exportMethodCall('dropIndex', [$this->asset->getName()]);
    }

    public static function exportRenameIndex(string $oldName, Index $index): string
    {
        return static::exportMethodCall('renameIndex', [$oldName, $index->getName()]);
    }
}
