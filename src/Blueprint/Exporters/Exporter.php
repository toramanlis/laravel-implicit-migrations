<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Blueprint\BlueprintDiff;

abstract class Exporter
{
    public const SUPPORTED_MODIFIERS = [];
    public const MODE_UP = 1;
    public const MODE_DOWN = 2;

    protected string $source;

    abstract protected function exportUp(): string;

    abstract protected function exportDown(): string;

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

    public function export(int $mode = self::MODE_UP): string
    {
        switch ($mode) {
            case static::MODE_UP:
                return $this->exportUp();
            case static::MODE_DOWN:
                // no break
            default:
                return $this->exportDown();
        }
    }

    public static function exportDefinition(
        Blueprint|Fluent|BlueprintDiff $definition,
        int $mode = self::MODE_UP
    ): string {
        return (new static($definition))->export($mode);
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

        $filteredItems = array_values(array_filter($items, function ($item) {
            return null === $item ? true : !empty($item);
        }));

        if (!empty($filteredItems)) {
            while (null === $filteredItems[0] || !trim($filteredItems[0])) {
                array_shift($filteredItems);
            }
        }

        if (!empty($filteredItems)) {
            while (null === last($filteredItems) || !trim(last($filteredItems))) {
                array_pop($filteredItems);
            }
        }

        return implode("\n", $filteredItems);
    }

    protected static function exportParameters(array $parameters): string
    {
        $items = [];

        foreach ($parameters as $value) {
            $items[] = static::varExport($value, true);
        }

        $totalLength = array_sum(array_map('strlen', $items));

        if ($totalLength > 80) {
            $parameters = str_replace("\n", "\n\t", implode(",\n", $items));
        } else {
            $parameters = implode(', ', $items);
        }

        if (strpos($parameters, "\n") === false) {
            $start = '';
            $end = '';
        } else {
            $start = "\n\t";
            $end = "\n";
        }

        return "{$start}" . $parameters . "{$end}";
    }

    protected static function exportMethodCall(string $methodName, array $parameters = [], array $modifiers = [])
    {
        sort($modifiers);
        $joinedModifiers = static::joinModifiers($modifiers);
        $parameters = static::exportParameters($parameters);
        return "\$table->{$methodName}({$parameters}){$joinedModifiers};";
    }

    protected static function extractModifiers(&$attributes)
    {
        $modifiers = [];

        foreach ($attributes as $attributeName => $value) {
            if (!in_array($attributeName, static::SUPPORTED_MODIFIERS) || in_array($value, [null, false])) {
                continue;
            }

            $parameters = true === $value ? '' : static::exportParameters([$value]);
            $modifiers[] = "->{$attributeName}({$parameters})";

            unset($attributes[$attributeName]);
        }

        return $modifiers;
    }

    protected static function joinModifiers($modifiers)
    {
        $concatenated = implode('', $modifiers);

        if (strlen($concatenated) > 90) {
            return str_replace('->', "\n\t->", $concatenated);
        }

        return $concatenated;
    }
}
