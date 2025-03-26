<?php

namespace Tests\Integration\Console\Commands;

use Toramanlis\Tests\Integration\BaseTestCase;

class GenerateMigrationCommandTest extends BaseTestCase
{
    public function testGeneratesMigrationCommand()
    {
        $this->carryModels(['Product.php']);
        $this->generate();
        $this->expectMigration('create_products_table');
    }
}
