<?php

namespace Toramanlis\Tests\Unit\Attributes;

use ReflectionProperty;
use Toramanlis\ImplicitMigrations\Attributes\PivotColumn;
use Toramanlis\Tests\Unit\BaseTestCase;

class PivotColumnTest extends BaseTestCase
{
    public function testIgnoresInferCall()
    {
        /** @var PivotColumn */
        $instance = $this->make(PivotColumn::class);

        /** @var ReflectionProperty */
        $reflection = $this->mock(ReflectionProperty::class)
            ->expects('getName')->never()->getMock();

        $instance->inferFromReflectionProperty($reflection);
    }
}
