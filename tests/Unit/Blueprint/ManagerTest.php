<?php

namespace Toramanlis\Tests\Unit\Blueprint;

use Toramanlis\ImplicitMigrations\Blueprint\Manager;
use Toramanlis\Tests\Data\Models\Affiliate;
use Toramanlis\Tests\Unit\BaseTestCase;

class ManagerTest extends BaseTestCase
{
    public function testHandlesUnrecognizedModel()
    {
        $this->carryModels(['Affiliate.php']);

        $manager = $this->make(Manager::class, ['blueprints' => []]);

        $manager->ensureIndexColumns([Affiliate::class]);

        $this->addToAssertionCount(1);
    }
}
