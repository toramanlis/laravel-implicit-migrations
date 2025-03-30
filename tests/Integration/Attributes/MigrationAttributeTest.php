<?php

namespace Toramanlis\Tests\Integration\Attributes;

use Toramanlis\Tests\Integration\BaseTestCase;

class MigrationAttributeTest extends BaseTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('database.implications.foreign_key', false);
    }

    public function testSkipsDisabledImplication()
    {
        $this->carryModels(['Affiliate.php']);
        $this->generate();

        $this->expectMigration(
            'create_affiliates_table',
            '0000_00_00_000000_0_implicit_migration_create_affiliates_table_without_foreign_key.php'
        );
    }
}
