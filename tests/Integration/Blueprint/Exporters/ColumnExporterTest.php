<?php

namespace Toramanlis\Tests\Integration\Blueprint\Exporters;

use Toramanlis\Tests\Integration\BaseTestCase;

class ColumnExporterTest extends BaseTestCase
{
    public function testCollapsesColumns()
    {
        $this->carryModels(['NumberWang.php']);
        $this->generate();
        $this->expectMigration('create_number_wangs_table');
    }
}
