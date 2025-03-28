<?php

namespace Toramanlis\Tests\Unit\Attributes;

use ReflectionProperty;
use Toramanlis\ImplicitMigrations\Attributes\Off;
use Toramanlis\Tests\Unit\BaseTestCase;

class MigrationAttributeTest extends BaseTestCase
{
    public function testInferringFallsBackToDefault()
    {
        /** @var Off */
        $off = $this->make(Off::class);

        /** @var ReflectionProperty */
        $reflection = $this->mock(ReflectionProperty::class);
        $off->inferFromReflectionProperty($reflection);

        $this->addToAssertionCount(1);
    }
}
