<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Illuminate\Database\Schema\Blueprint;
use ReflectionClass;
use ReflectionProperty;

abstract class MigrationAttribute
{
    abstract public function applyToBlueprint(Blueprint $table): Blueprint;

    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
    }

    public function inferFromReflectionClass(ReflectionClass $reflection): void
    {
    }

    public function inferFromExistingData(): void
    {
    }
}
