<?php

namespace Toramanlis\Tests\Unit\Generator;

use Illuminate\Database\Eloquent\Relations\Relation;
use Toramanlis\ImplicitMigrations\Exceptions\ImplicationException;
use Toramanlis\ImplicitMigrations\Generator\RelationshipResolver;
use Toramanlis\Tests\Unit\BaseTestCase;

class RelationshipResolverTest extends BaseTestCase
{
    public function testHandlesInvalidRelationship()
    {
        $this->expectException(ImplicationException::class);
        $this->expectExceptionCode(ImplicationException::CODE_RL_UNKNOWN);

        RelationshipResolver::resolve($this->createMock(Relation::class));
    }
}
