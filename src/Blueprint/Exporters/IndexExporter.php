<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Exporters;

use Exception;
use Illuminate\Support\Fluent;

class IndexExporter extends Exporter
{
    public const SUPPORTED_MODIFIERS = [
        'language',
        'on',
        'references',
        'onUpdate',
        'onDelete',
    ];

    public function __construct(protected Fluent $definition)
    {
    }

    public static function renameIndex(string $from, string $to)
    {
        return static::exportMethodCall('renameIndex', [$from, $to]);
    }

    protected function exportUp(): string
    {
        $method = $this->definition->name;

        $parameters = [
            count($this->definition->columns) > 1 ? $this->definition->columns : $this->definition->columns[0],
        ];

        if (null !== $this->definition->index) {
            $parameters[] = $this->definition->index;
        }

        if (!in_array($method, ['spatialIndex', 'foreign']) && null !== $this->definition->algorithm) {
            if (null === $this->definition->index) {
                $parameters[] = null;
            }
            $parameters[] = $this->definition->algorithm;
        }

        $attributes = $this->definition->getAttributes();
        $modifiers = static::extractModifiers($attributes);

        return $this->exportMethodCall($method, $parameters, $modifiers);
    }

    protected function exportDown(): string
    {
        return $this->exportMethodCall('drop' . ucfirst($this->definition->name), [$this->definition->index]);
    }
}
