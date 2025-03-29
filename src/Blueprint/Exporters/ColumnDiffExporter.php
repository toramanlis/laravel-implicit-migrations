<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

class ColumnDiffExporter extends ColumnExporter
{
    protected static function joinModifiers($modifiers)
    {
        $modifiers[] = '->change()';
        return parent::joinModifiers($modifiers);
    }

    /**
     * @param string $from
     * @param string $to
     * @return string
     */
    public static function renameColumn(string $from, string $to)
    {
        $parameters = static::exportParameters([$from, $to]);
        return "\$table->renameColumn({$parameters});";
    }
}
