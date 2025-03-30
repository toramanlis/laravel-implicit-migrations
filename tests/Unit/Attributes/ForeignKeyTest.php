<?php

namespace Toramanlis\Tests\Unit\Attributes;

use Exception as BaseException;
use Toramanlis\ImplicitMigrations\Attributes\ForeignKey;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;
use Toramanlis\ImplicitMigrations\Attributes\Exception;
use Toramanlis\Tests\Unit\BaseTestCase;

class ForeignKeyTest extends BaseTestCase
{
    public function testFallsBackWhenNoModel()
    {
        /** @var ForeignKey */
        $foreignKey = $this->make(ForeignKey::class, ['column' => 'something_id', 'on' => 'others']);

        $referenceTable = $this->make(SimplifyingBlueprint::class, ['tableName' => 'others']);

        /** @var SimplifyingBlueprint */
        $table = $this->mock(SimplifyingBlueprint::class)
            ->expects('unsignedBigInteger')->once()->with('something_id')->getMock()
            ->expects('getColumns')->once()->andReturn([])->getMock();

        $foreignKey->ensureColumns($table, ['others' => $referenceTable], []);
    }

    public function testInvalidatesWhenNoColumn()
    {
        $foreignKey = $this->make(ForeignKey::class, ['column' => 'something_id', 'on' => 'others']);

        /** @var SimplifyingBlueprint */
        $table = $this->make(SimplifyingBlueprint::class, ['tableName' => 'things']);

        $referenceTable = $this->mock(SimplifyingBlueprint::class)
            ->expects('getColumns')->andThrow(BaseException::class)->getMock();

        $this->expectException(Exception::class);
        $this->expectExceptionCode(Exception::CODE_FK_NO_COL);

        $foreignKey->ensureColumns($table, ['others' => $referenceTable], []);
    }
}
