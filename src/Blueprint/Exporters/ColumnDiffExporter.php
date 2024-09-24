<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

class ColumnDiffExporter extends ColumnExporter
{
    protected static function joinModifiers($modifiers)
    {
        $modifiers[] = '->change()';
        return parent::joinModifiers($modifiers);
    }

    public static function renameColumn(string $from, string $to)
    {
        return static::exportMethodCall('renameColumn', [$from, $to]);
    }
}
