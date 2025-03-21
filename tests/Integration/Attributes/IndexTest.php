<?php

namespace Toramanlis\Tests\Integration\Attributes;

use Toramanlis\Tests\Integration\BaseTestCase;

class IndexTest extends BaseTestCase
{
    public function testEnsureColumns()
    {
        $this->carryModels(['Refund.php']);
        $this->generate();
        $this->expectMigration('create_refunds_table');
    }
}
