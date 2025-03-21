<?php

namespace Toramanlis\Tests\Integration;

use Exception;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Toramanlis\ImplicitMigrations\Providers\ImplicitMigrationsServiceProvider;

use function Orchestra\Testbench\package_path;

abstract class BaseTestCase extends TestCase
{
    use RefreshDatabase;

    protected array $itemsCarried = [];

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

    protected function getApp(): Application
    {
        if (!$this->app) {
            throw new Exception('Application not set.');
        }

        return $this->app;
    }

    protected static function path(array $parts): string
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    protected static function testPath(array $parts): string
    {
        return static::path(['tests', ...$parts]);
    }

    protected static function dataPath($parts): string
    {
        return static::testPath(['_data', ...$parts]);
    }

    protected static function tmpPath($parts): string
    {
        return static::testPath(['_tmp', ...$parts]);
    }

    protected function carryData(string|array $files, string $directory = ''): void
    {
        $directory = trim($directory, DIRECTORY_SEPARATOR);

        if (is_string($files)) {
            $this->carryData([$files], $directory);
            return;
        }

        foreach ($files as $key => $value) {
            if (is_array($value)) {
                $subdirectory = is_string($key) ? trim($key, DIRECTORY_SEPARATOR) : '';
                $this->carryData($value, $directory . DIRECTORY_SEPARATOR . $subdirectory);
                continue;
            }

            if (!is_string($value)) {
                continue;
            }

            $relativeTarget = trim($value, DIRECTORY_SEPARATOR);
            $relativeSource = is_string($key) ? trim($key, DIRECTORY_SEPARATOR) : $relativeTarget;

            $source = package_path(static::dataPath([$directory, $relativeSource]));
            $target = package_path(static::tmpPath([$directory, $relativeTarget]));

            if (is_dir($source)) {
                $entries = [];

                foreach (array_diff(scandir($source) ?: [], ['.', '..']) as $child) {
                    $entries[$relativeSource . DIRECTORY_SEPARATOR . $child] = $value . DIRECTORY_SEPARATOR . $child;
                }

                $this->carryData($entries, $directory);
                continue;
            }

            $targetPathParts = explode(DIRECTORY_SEPARATOR, $relativeTarget);
            array_pop($targetPathParts);

            $subpath = rtrim(package_path('tests' . DIRECTORY_SEPARATOR . '_tmp'), DIRECTORY_SEPARATOR);
            foreach ($targetPathParts as $pathPart) {
                if (!$pathPart) {
                    continue;
                }

                $subpath = $subpath . DIRECTORY_SEPARATOR . $pathPart;

                if (!is_dir($subpath)) {
                    mkdir($subpath, 0755);
                    $this->itemsCarried[] = $subpath;
                }
            }

            file_put_contents($target, file_get_contents($source));
            $this->itemsCarried[] = $target;
        }
    }

    protected function carryModels(array $models)
    {
        $this->carryData([static::path(['app', 'Models']) => $models]);
    }

    protected function carryMigrations(array $migrations)
    {
        $this->carryData([static::path(['database', 'migrations']) => $migrations]);
    }

    protected function cleanTmp()
    {
        foreach (array_reverse(array_merge($this->itemsCarried, $this->getCreatedMigrations())) as $item) {
            if (is_dir($item)) {
                rmdir($item);
            } else {
                unlink($item);
            }
        }

        $this->itemsCarried = [];
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
        ]);
        $app['config']->set('database.auto_infer_migrations', false);
    }

    public function tearDown(): void
    {
        $this->cleanTmp();
        parent::tearDown();
    }

    public function __destruct()
    {
        $this->cleanTmp();
    }
}
