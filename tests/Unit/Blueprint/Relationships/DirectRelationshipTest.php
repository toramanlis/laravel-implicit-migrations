<?php

namespace Toramanlis\Tests\Unit\Blueprint\Relationships;

use Toramanlis\ImplicitMigrations\Blueprint\Relationships\DirectRelationship;
use Toramanlis\ImplicitMigrations\Exceptions\ImplicationException;
use Toramanlis\Tests\Unit\BaseTestCase;

class DirectRelationshipTest extends BaseTestCase
{
    protected DirectRelationship $instance;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var DirectRelationship */
        $instance = $this->make(DirectRelationship::class);
        $this->instance = $instance;
    }

    public function testGetParentTable()
    {
        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_RL_NO_PARENT);
        $this->instance->getParentTable();
    }

    public function testGetRelatedTable()
    {
        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_RL_NO_RELATED);
        $this->instance->getRelatedTable();
    }

    public function testGetForeignKey()
    {
        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_RL_NO_FOREIGN);
        $this->instance->getForeignKey();
    }

    public function testGetLocalKey()
    {
        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_RL_NO_LOCAL);
        $this->instance->getLocalKey();
    }
}
