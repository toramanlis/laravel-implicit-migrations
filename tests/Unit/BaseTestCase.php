<?php

namespace Toramanlis\Tests\Unit;

use Toramanlis\Tests\BaseTestCase as TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Facades\DB;

abstract class BaseTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->mock(Connection::class)
            ->expects('getSchemaGrammar')
            ->atLeast()
            ->times(0)
            ->andReturn($this->mock(Grammar::class))
            ->getMock();
        DB::shouldReceive('connection')->atLeast()->times(0)->andReturn($connection);
    }
}
