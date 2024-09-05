<?php

namespace Toramanlis\ImplicitMigrations\Exporters;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\TableDiff;

abstract class AbstractAssetExporter
{
    public const MODE_CREATE = 1;
    public const MODE_DROP = 2;

    protected int $mode;

    abstract public function exportCreate(): string;

    abstract public function exportDrop(): string;

    public static function varExport(mixed $variable)
    {
        $components = [];
        if (is_array($variable)) {
            $i = 0;
            foreach ($variable as $key => $value) {
                $valueRepresentation = static::varExport($value);
                if ($i === $key) {
                    $i++;
                    $components[] = $valueRepresentation;
                    continue;
                }

                $components[] = static::varExport($key) . ' => ' . $valueRepresentation;
            }

            $totalLength = array_sum(array_map('strlen', $components));

            if ($totalLength > 80) {
                $start = "\n\t";
                $end = ",\n";
                $separator = ",\n\t";
            } else {
                $start = '';
                $end = '';
                $separator = ', ';
            }

            return "[{$start}" . implode($separator, $components) . "{$end}]";
        }

        return (string) var_export($variable, true);
    }

    public function export(int $mode = self::MODE_CREATE): string
    {
        switch ($mode) {
            case static::MODE_CREATE:
                return $this->exportCreate();
            case static::MODE_DROP:
                // no break
            default:
                return $this->exportDrop();
        }
    }

    public static function exportAsset(
        AbstractAsset|TableDiff|ColumnDiff $asset,
        int $mode = self::MODE_CREATE
    ): string {
        return (new static($asset))->export($mode);
    }

    /**
     * @param array<string|array> $exports
     * @return string
     */
    protected static function joinExports(array $exports): string
    {
        $items = [];

        foreach ($exports as $export) {
            $items[] = is_array($export) ? static::joinExports($export) : $export;
        }

        return implode("\n", array_filter($items));
    }

    protected static function exportParameters(array $parameters): string
    {
        $items = [];

        foreach ($parameters as $value) {
            $items[] = static::varExport($value, true);
        }

        $totalLength = array_sum(array_map('strlen', $items));

        if ($totalLength > 80) {
            return str_replace("\n", "\n\t", implode(",\n", $items));
        } else {
            return implode(', ', $items);
        }
    }

    protected static function exportMethodCall(string $methodName, array $parameters)
    {
        $parameters = static::exportParameters($parameters);

        if (strpos($parameters, "\n") === false) {
            $start = '';
            $end = '';
        } else {
            $start = "\n\t";
            $end = "\n";
        }

        return "\$table->{$methodName}({$start}" . $parameters . "{$end});";
    }
}
