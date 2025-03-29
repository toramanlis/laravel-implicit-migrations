<?php

namespace Toramanlis\Tests\Unit\Blueprint\Relationships;

use Toramanlis\ImplicitMigrations\Blueprint\Relationships\IndirectRelationship;
use Toramanlis\ImplicitMigrations\Exceptions\ImplicationException;
use Toramanlis\Tests\Unit\BaseTestCase;

class IndirectRelationshipTest extends BaseTestCase
{
    public function testSettersGetters()
    {
        /** @var IndirectRelationship */
        $instance = $this->make(IndirectRelationship::class);
        $relatedTables = ['things', 'others'];
        $foreignKeys = ['things' => 'thing_id', 'others' => 'other_id'];
        $localKeys = ['things' => 'id', 'others' => 'id'];
        $pivotColumn = 'thingable_thing';

        $instance->setRelatedTables($relatedTables);
        $this->assertEquals($relatedTables, $instance->getRelatedTables());

        $instance->setForeignKeys($foreignKeys);
        $this->assertEquals($foreignKeys, $instance->getForeignKeys());

        $instance->setLocalKeys($localKeys);
        $this->assertEquals($localKeys, $instance->getlocalKeys());

        $instance->addPivotColumn($pivotColumn);
        $this->assertEquals([$pivotColumn], $instance->getPivotColumns());

        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_RL_NO_PIVOT);

        $instance->getPivotTable();
    }
}
