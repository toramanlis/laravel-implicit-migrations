<?php

namespace Toramanlis\ImplicitMigrations\Exporters;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class ColumnExporter extends AbstractAssetExporter
{
    public function __construct(
        protected Column $asset
    ) {
    }

    public function exportCreate(): string
    {
        return static::exportMethodCall('addColumn', [
            $this->asset->getName(),
            Type::lookupName($this->asset->getType()),
            $this->getColumnOptions($this->asset),
        ]);
    }

    public function exportDrop(): string
    {
        return static::exportMethodCall('dropColumn', [$this->asset->getName()]);
    }

    protected function getColumnOptions(Column $column): array
    {

        $optionNames = [
            'Length', 'Precision', 'Scale', 'Unsigned', 'Fixed', 'Notnull',
            'Default', 'Autoincrement', 'ColumnDefinition', 'Comment',
        ];

        $options = [];

        $defaultColumn = new Column('not_' . $column->getName(), $column->getType());

        foreach ($optionNames as $optionName) {
            $getter = "get{$optionName}";

            $value = $column->$getter();
            $defaultValue = $defaultColumn->$getter();

            if (null === $value || $value === $defaultValue) {
                continue;
            }

            $options[$optionName] = $value;
        }

        return $options;
    }
}
