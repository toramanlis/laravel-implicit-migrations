<?php

namespace Toramanlis\Tests\Unit\Attributes;

use Exception;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;
use Toramanlis\ImplicitMigrations\Exceptions\ImplicationException;
use Toramanlis\Tests\Unit\BaseTestCase;

class ColumnTest extends BaseTestCase
{
    public function testInvalidatesWhenNoName()
    {
        $column = $this->make(Column::class);
        $table = $this->make(SimplifyingBlueprint::class, ['tableName' => 'things']);

        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_COL_NO_NAME);

        $column->applyToBlueprint($table);
    }

    public function testInvalidatesWhenUnexpectedError()
    {
        $column = $this->make(Column::class);

        /** @var SimplifyingBlueprint */
        $table = $this->mock(SimplifyingBlueprint::class)
            ->expects('getTable')->twice()->andReturnUsing(fn() => throw new Exception(), fn () => 'things')->getMock();

        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_COL_GENERIC);

        $column->applyToBlueprint($table);
    }

    public function testFallsBackToDefaultWhenUnrecognizedParameter()
    {
        $column = $this->make(Column::class, ['type' => 'integer', 'name' => 'thingy', 'length' => 255]);

        /** @var SimplifyingBlueprint */
        $table = $this->mock(SimplifyingBlueprint::class)
            ->expects('addColumn')->once()->getMock();

        $column->applyToBlueprint($table);
    }
}
