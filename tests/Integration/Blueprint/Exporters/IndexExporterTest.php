<?php

namespace Toramanlis\Tests\Integration\Blueprint\Exporters;

use Toramanlis\Tests\Integration\BaseTestCase;

class IndexExporterTest extends BaseTestCase
{
    public function testDropsIndex()
    {
        $this->carryModels(['Numberwang.php']);
        $this->carryMigrations([
            '0000_00_00_000000_0_implicit_migration_create_numberwangs_table_with_extra_index.php'
        ]);
        $this->generate();
        $this->expectMigration('update_numberwangs_table');
    }
}
