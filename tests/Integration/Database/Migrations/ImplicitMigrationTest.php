<?php

namespace Tests\Integration\Database\Migrations;

use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Toramanlis\Tests\Integration\BaseTestCase;

class ImplicitMigrationTest extends BaseTestCase
{
    public function testCreate()
    {
        $this->carryMigrations(['0000_00_00_000000_0_implicit_migration_create_products_table.php']);

        $statements = [];

        Event::listen(MigrationEnded::class, function (MigrationEnded $event) use (&$statements) {
            $log = DB::getQueryLog();
            $this->assertNotEmpty($log);
            if ('up' === $event->method) {
                $statements[] = $log[count($log) - 3];
                $statements[] = $log[count($log) - 2];
                $statements[] = $log[count($log) - 1];
            } else {
                $statements[] = $log[count($log) - 1];
            }
        });

        DB::enableQueryLog();
        $this->migrate();
        $this->rollback();

        $this->assertStringContainsString('create table "products"', $statements[0]['query']);
        $this->assertStringContainsString('create index "products_name_index"', $statements[1]['query']);
        $this->assertStringContainsString('create index "products_stock_index"', $statements[2]['query']);
        $this->assertStringContainsString('drop table "products"', $statements[3]['query']);
    }
}
