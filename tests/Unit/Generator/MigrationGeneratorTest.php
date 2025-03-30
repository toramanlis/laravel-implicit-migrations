<?php

namespace Toramanlis\Tests\Unit\Generator;

use stdClass;
use Toramanlis\ImplicitMigrations\Blueprint\Manager;
use Toramanlis\ImplicitMigrations\Generator\MigrationGenerator;
use Toramanlis\Tests\Unit\BaseTestCase;

class MigrationGeneratorTest extends BaseTestCase
{
    public function testHandlesOffModel()
    {
        $this->mock(Manager::class)
            ->expects('applyRelationshipsToBlueprints')->once()->getMock()
            ->expects('ensureIndexColumns')->once()->getMock()
            ->expects('getRelationshipMap')->once()->andReturn([])->getMock()
            ->expects('getBlueprints')->once()->andReturn([])->getMock();

        $generator = $this->make(MigrationGenerator::class, ['existingMigrations' => []]);
        $generator->generate([stdClass::class]);

        $this->addToAssertionCount(1);
    }
}
