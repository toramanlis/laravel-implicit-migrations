<?php

namespace Toramanlis\Tests\Integration\Attributes;

use Toramanlis\Tests\Integration\BaseTestCase;

class ForeignKeyTest extends BaseTestCase
{
    public function testPropertyAttribute()
    {
        $this->carryModels(['Affiliate.php', 'Store.php']);

        $this->generate();
        $this->expectMigration('create_affiliates_table');
    }

    public function testClassAttribute()
    {
        $this->carryModels(['Link.php', 'Affiliate.php', 'Redirection.php']);

        $this->generate();
        $this->expectMigration('create_links_table');
        $this->expectMigration('create_redirections_table');
    }
}
