<?php

namespace Toramanlis\Tests\Integration;

use Toramanlis\Tests\BaseTestCase as TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Toramanlis\ImplicitMigrations\Providers\ImplicitMigrationsServiceProvider;

use function Orchestra\Testbench\package_path;

abstract class BaseTestCase extends TestCase
{
    use RefreshDatabase;

    protected function generate(): void
    {
        $this->artisan('implicit-migrations:generate');
    }

    protected function migrate(): void
    {
        $this->artisan('migrate');
    }

    protected function rollback(?int $steps = null): void
    {
        $this->artisan('migrate:rollback', $steps ? [
            '--step' => $steps,
        ] : []);
    }

    protected function getCreatedMigrations($nameMatcher = '.*?'): array
    {
        $pattern = '/\d{4}_\d{2}_\d{2}_\d{6}_\d{1,2}_implicit_migration_' . $nameMatcher . '\.php/';
        $output = $this->getActualOutputForAssertion();

        $migrations = [];
        foreach (explode("\n", $output) as $line) {
            if (preg_match('/Created migration: (.*)/', $line, $matches)) {
                if (!preg_match($pattern, $matches[1])) {
                    continue;
                }

                if (!file_exists($matches[1])) {
                    continue;
                }

                $migrations[] = $matches[1];
            }
        }

        return $migrations;
    }

    protected function expectMigrationCreation(bool $takesPlace, $nameMatcher = '.*?', $reference = null): void
    {
        $matchingMigrations = $this->getCreatedMigrations($nameMatcher);
        $migrationFileCreated = count($matchingMigrations) > 0;

        if ($takesPlace) {
            $this->assertTrue($migrationFileCreated, 'No migration created matching the pattern: ' . $nameMatcher);
        } else {
            $this->assertFalse($migrationFileCreated, 'A migration created matching the pattern: ' . $nameMatcher);
            return;
        }

        if (!$migrationFileCreated) {
            return;
        }

        $reference = $reference ?? "0000_00_00_000000_0_implicit_migration_{$nameMatcher}.php";

        $contentsMatch = false;

        $referenceFile = package_path(static::dataPath([
            'database', 'migrations', $reference
        ]));

        if (file_exists($referenceFile)) {
            foreach ($matchingMigrations as $migration) {
                $contentsMatch = file_get_contents($referenceFile) === file_get_contents($migration);
                break;
            }
        } else {
            foreach ($matchingMigrations as $migration) {
                $contentsMatch = preg_match($reference, file_get_contents($migration));
                break;
            }
        }

        $this->assertTrue($contentsMatch, 'Migration contents do not match the reference: ' . $reference);
    }

    public function expectMigration($nameMatcher = '.*?', $reference = null): void
    {
        $this->expectMigrationCreation(true, $nameMatcher, $reference);
    }

    public function expectNoMigration($nameMatcher = '.*?'): void
    {
        $this->expectMigrationCreation(false, $nameMatcher);
    }


    protected function getPackageProviders($app)
    {
        return [
            ImplicitMigrationsServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $migrationsPath = package_path(static::tmpPath(['database', 'migrations']));

        if (!is_dir($migrationsPath)) {
            mkdir($migrationsPath, 0755, true);
        }

        $this->loadMigrationsFrom($migrationsPath);
    }

    protected function defineEnvironment($app)
    {
        $modelsPath = package_path(static::tmpPath(['app', 'Models']));

        if (!is_dir($modelsPath)) {
            mkdir($modelsPath, 0755, true);
        }

        $app['config']->set('database.model_paths', [
            $modelsPath,
            null
        ]);
        $app['config']->set('database.auto_infer_migrations', false);
    }

    protected function cleanTmp()
    {
        $this->itemsCarried = array_merge($this->itemsCarried, $this->getCreatedMigrations());
        parent::cleanTmp();
    }
}
