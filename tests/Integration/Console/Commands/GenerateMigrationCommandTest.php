<?php

namespace Tests\Integration\Console\Commands;

use PHPUnit\Framework\Attributes\CoversClass;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\Index;
use Toramanlis\ImplicitMigrations\Attributes\Table;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\ColumnExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\Exporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\IndexExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Manager;
use Toramanlis\ImplicitMigrations\Console\Commands\GenerateMigrationCommand;
use Toramanlis\ImplicitMigrations\Generator\MigrationGenerator;
use Toramanlis\ImplicitMigrations\Generator\TemplateManager;
use Toramanlis\ImplicitMigrations\Providers\ImplicitMigrationsServiceProvider;
use Toramanlis\Tests\Integration\BaseTestCase;

use function Orchestra\Testbench\artisan;

#[CoversClass(GenerateMigrationCommand::class)]
#[CoversClass(Table::class)]
#[CoversClass(Index::class)]
#[CoversClass(Column::class)]
#[CoversClass(Exporter::class)]
#[CoversClass(ColumnExporter::class)]
#[CoversClass(IndexExporter::class)]
#[CoversClass(TableExporter::class)]
#[CoversClass(Manager::class)]
#[CoversClass(MigrationGenerator::class)]
#[CoversClass(TemplateManager::class)]
#[CoversClass(ImplicitMigrationsServiceProvider::class)]
class GenerateMigrationCommandTest extends BaseTestCase
{
    public function testGenerateMigrationCommand()
    {
        $this->assertTrue(true);
        $this->carryData('app');
        artisan($this->getApp(), 'implicit-migrations:generate');
        $this->expectMigration('create_products_table');
    }
}
