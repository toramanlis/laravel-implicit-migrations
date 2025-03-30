<?php

namespace Toramanlis\Tests;

use Closure;
use Exception;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase;

use function Orchestra\Testbench\package_path;

abstract class BaseTestCase extends TestCase
{
    protected array $itemsCarried = [];

    protected function getApp(): Application
    {
        if (!$this->app) {
            throw new Exception('Application not set.');
        }

        return $this->app;
    }

    protected function mock($abstract, ?Closure $mock = null)
    {
        $concrete = parent::mock($abstract, $mock);
        $this->getApp()->bind($abstract, fn () => $concrete);
        return $concrete;
    }

    protected function make($abstract, array $parameters = [])
    {
        return $this->getApp()->make($abstract, $parameters);
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
        foreach (array_reverse($this->itemsCarried) as $item) {
            if (is_dir($item)) {
                rmdir($item);
            } else {
                unlink($item);
            }
        }

        $this->itemsCarried = [];
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
