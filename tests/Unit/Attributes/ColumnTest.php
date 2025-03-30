<?php

namespace Toramanlis\Tests\Unit\Attributes;

use Exception as BaseException;
use Illuminate\Support\Facades\Config;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;
use Toramanlis\ImplicitMigrations\Attributes\Exception;
use Toramanlis\Tests\Unit\BaseTestCase;

class ColumnTest extends BaseTestCase
{
    public function testInvalidatesWhenNoName()
    {
        $column = $this->make(Column::class);
        $table = $this->make(SimplifyingBlueprint::class, ['tableName' => 'things']);

        $this->expectException(Exception::class);
        $this->expectExceptionCode(Exception::CODE_COL_NO_NAME);

        $column->apply($table);
    }

    public function testInvalidatesWhenUnexpectedError()
    {
        $column = $this->make(Column::class);

        /** @var SimplifyingBlueprint */
        $table = $this->mock(SimplifyingBlueprint::class)
            ->expects('getTable')->twice()
            ->andReturnUsing(fn() => throw new BaseException(), fn () => 'things')->getMock();

        $this->expectException(Exception::class);
        $this->expectExceptionCode(Exception::CODE_COL_GENERIC);

        $column->apply($table);
    }

    public function testFallsBackToDefaultWhenUnrecognizedParameter()
    {
        $column = $this->make(Column::class, ['type' => 'integer', 'name' => 'thingy', 'length' => 255]);

        /** @var SimplifyingBlueprint */
        $table = $this->mock(SimplifyingBlueprint::class)
            ->expects('addColumn')->once()->getMock();

        $column->apply($table);
    }

    public function testDoesNotApplyWhenDisabled()
    {
        Config::set('database.implications.column', false);

        $column = $this->make(Column::class, ['type' => 'integer', 'name' => 'thingy']);

        /** @var SimplifyingBlueprint */
        $table = $this->mock(SimplifyingBlueprint::class)
            ->expects('integer')->never()->getMock();

        $column->apply($table);
    }
}
