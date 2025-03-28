<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

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
}
