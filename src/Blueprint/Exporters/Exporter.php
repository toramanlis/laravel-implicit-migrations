<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Blueprint\BlueprintDiff;

abstract class Exporter
{
    public const SUPPORTED_MODIFIERS = [];
    public const MODE_UP = 1;
    public const MODE_DOWN = 2;

    protected const WRAP_LIMIT = 80;

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

            if ($totalLength > static::WRAP_LIMIT) {
                $start = "\n\t";
                $end = ",\n";
                $separator = ",\n\t";
            } else {
                $start = '';
                $end = '';
                $separator = ', ';
            }

            return "[{$start}" . implode($separator, $components) . "{$end}]";
        } elseif (is_null($variable)) {
            return 'null';
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
        return App::make(static::class, ['definition' => $definition])->export($mode);
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

        while (
            !empty($filteredItems) &&
            (null === $filteredItems[0] || !trim($filteredItems[0]))
        ) {
            array_shift($filteredItems);
        }

        while (
            !empty($filteredItems) &&
            (null === last($filteredItems) || !trim(last($filteredItems)))
        ) {
            array_pop($filteredItems);
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

        if ($totalLength > static::WRAP_LIMIT) {
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
        $unmodified = "\$table->{$methodName}({$parameters})";

        if (strlen($unmodified) + strpos("{$joinedModifiers}\n", "\n") > static::WRAP_LIMIT) {
            $unmodified .= "\n\t";
        }

        return "{$unmodified}{$joinedModifiers};";
    }

    protected static function makeModifier($name, $parameters)
    {
        $parameterString = static::exportParameters($parameters);
        return "->{$name}({$parameterString})";
    }

    protected static function extractModifiers(&$attributes)
    {
        $modifiers = [];

        foreach ($attributes as $attributeName => $value) {
            if (!in_array($attributeName, static::SUPPORTED_MODIFIERS) || in_array($value, [null, false])) {
                continue;
            }

            $modifiers[] = static::makeModifier($attributeName, true === $value ? [] : [$value]);

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
