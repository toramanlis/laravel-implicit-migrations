<?php

namespace Toramanlis\Tests\Unit\Generator;

use Illuminate\Database\Eloquent\Relations\Relation;
use Toramanlis\ImplicitMigrations\Attributes\Exception;
use Toramanlis\ImplicitMigrations\Generator\RelationshipResolver;
use Toramanlis\Tests\Unit\BaseTestCase;

class RelationshipResolverTest extends BaseTestCase
{
    public function testHandlesInvalidRelationship()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(Exception::CODE_RL_UNKNOWN);

        RelationshipResolver::resolve($this->createMock(Relation::class));
    }
}
