<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

trait SortsColumns
{
    protected function getSortedExports(array $exporters)
    {
        $exports = [];
        $ids = [];
        $primary = null;

        foreach ($exporters as $exporter) {
            $columnExport = $exporter->export();

            if ($exporter->definition->primary) {
                $primary = $columnExport;
            } elseif ($exporter->foreignKey) {
                array_unshift($exports, $columnExport);
            } elseif ('id' === $exporter->getCollapsedType()) {
                if ('id' == $exporter->definition->name) {
                    array_unshift($ids, $columnExport);
                } else {
                    $ids[] = $columnExport;
                }
            } else {
                $exports[] = $columnExport;
            }
        }

        if ($primary) {
            array_unshift($ids, $primary);
        }

        array_unshift($exports, ...$ids);

        return $exports;
    }
}
