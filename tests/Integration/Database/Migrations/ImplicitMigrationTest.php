<?php

namespace Tests\Integration\Database\Migrations;

use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationEvent;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\NoPendingMigrations;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\SchemaDumped;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Toramanlis\Tests\Integration\BaseTestCase;

class ImplicitMigrationTest extends BaseTestCase
{
    public function testCreate()
    {
        $this->carryMigrations(['0000_00_00_000000_0_implicit_migration_create_roles_table.php']);

        Event::listen(MigrationEnded::class, function (MigrationEnded $event) {
            $log = DB::getQueryLog();
            $this->assertNotEmpty($log);
            if ('up' === $event->method) {
                $this->assertStringContainsString('create table "roles"', end($log)['query']);
            } else {
                $this->assertStringContainsString('drop table "roles"', end($log)['query']);
            }
        });

        DB::enableQueryLog();
        $this->migrate();
        $this->rollback();
    }
}
