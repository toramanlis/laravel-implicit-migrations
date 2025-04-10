<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionProperty;

abstract class MigrationAttribute
{
    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
    }

    public function inferFromReflectionClass(ReflectionClass $reflection): void
    {
    }

    public function inferFromExistingData(): void
    {
    }

    public static function enabled(): bool
    {
        $key = Str::snake(last(explode('\\', static::class)));
        return Config::get("database.implications.{$key}", true);
    }
}
