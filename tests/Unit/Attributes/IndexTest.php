<?php

namespace Toramanlis\Tests\Unit\Attributes;

use ReflectionProperty;
use Toramanlis\ImplicitMigrations\Attributes\Index;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;
use Toramanlis\ImplicitMigrations\Exceptions\ImplicationException;
use Toramanlis\Tests\Unit\BaseTestCase;

class IndexTest extends BaseTestCase
{
    public function testInvalidatesInvalidType()
    {
        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_IDX_NO_TYPE);

        $this->make(Index::class, ['name' => 'something', 'type' => 'nothing']);
    }

    public function testInvalidatesNoColumn()
    {
        $index = $this->make(Index::class);
        $table = $this->make(SimplifyingBlueprint::class, ['tableName' => 'things']);

        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_IDX_NO_COL);
        $index->applyToBlueprint($table);
    }

    public function testSkipsInferringFromPropertyWhenColumnsGiven()
    {
        /** @var Index */
        $index = $this->make(Index::class, ['column' => ['some', 'thing']]);

        /** @var ReflectionProperty */
        $reflection = $this->mock(ReflectionProperty::class)
            ->expects('getName')->never()->getMock();

        $index->inferFromReflectionProperty($reflection);
    }

    public function testSkipstEnsuringColumnWhenNoColumns()
    {
        /** @var Index */
        $index = $this->make(Index::class);

        /** @var SimplifyingBlueprint */
        $talbe = $this->mock(SimplifyingBlueprint::class)
            ->expects('getColumns')->never()->getMock();

        $index->ensureColumns($talbe);
    }
}
