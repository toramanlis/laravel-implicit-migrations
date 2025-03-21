<?php

namespace Tests\Integration\Generator;

use Toramanlis\ImplicitMigrations\Console\Commands\GenerateMigrationCommand;
use Toramanlis\ImplicitMigrations\Generator\MigrationGenerator;
use Toramanlis\Tests\Integration\BaseTestCase;

use function Orchestra\Testbench\package_path;

class MigrationGeneratorTest extends BaseTestCase
{
    public function testRecognizesExistingMigration()
    {
        $this->carryModels(['Product.php']);
        $this->carryMigrations(['0000_00_00_000000_0_implicit_migration_create_products_table.php']);

        $this->generate();
        $this->expectNoMigration();
    }

    public function testRecognizesPartiallyExistingMigration()
    {
        $this->carryModels(['Product.php']);
        $this->carryMigrations([
            '0000_00_00_000000_0_implicit_migration_create_products_table.php',
            '0000_00_00_000000_0_implicit_migration_update_products_table_partial.php',
        ]);

        $this->generate();
        $this->expectMigration(
            'update_products_table',
            '0000_00_00_000000_0_implicit_migration_update_products_table_missing_parts.php'
        );
    }

    public function testSkipsExistingMigrationFile()
    {
        $this->carryMigrations(['0000_00_00_000000_fake_migration.php']);

        $migrationGeneratorMock = $this->mock(MigrationGenerator::class)
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'fake_table' => [
                    'modelName' => 'stdClass',
                    'mode' => 'create',
                ],
            ])
            ->getMock();

        $this->getApp()->bind(MigrationGenerator::class, fn() => $migrationGeneratorMock);

        $dummyMigration = package_path(static::tmpPath([
            'database', 'migrations', '0000_00_00_000000_fake_migration.php'
        ]));

        /** @var MockInterface|GenerateMigrationCommand */
        $commandMock = $this->partialMock(GenerateMigrationCommand::class)
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive('generateMigrationFilePath')
            ->once()
            ->andReturn($dummyMigration)
            ->getMock();
        $commandMock->shouldReceive('argument')
            ->once()
            ->andReturn(['FakeModel']);

        $commandMock->handle();
        $this->expectOutputString("\tMigration file {$dummyMigration} already exists. Skipping\n");
        $this->expectNoMigration();
    }

    public function testRenameTable()
    {
        $this->carryModels(['Item.php']);
        $this->carryMigrations(['0000_00_00_000000_0_implicit_migration_create_items_table.php']);

        $this->generate();
        $this->expectMigration('update_items_table', '0000_00_00_000000_0_implicit_migration_update_items_table.php');
    }
}
