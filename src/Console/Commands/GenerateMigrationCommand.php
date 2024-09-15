<?php

namespace Toramanlis\ImplicitMigrations\Console\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Toramanlis\ImplicitMigrations\Generator\MigrationGenerator;
use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;

#[AsCommand(name: 'implicit-migrations:generate')]
class GenerateMigrationCommand extends Command
{
    protected const TEMPLATE_NAME = 'migration.php.tpl';

    /** @var string */
    protected $signature = 'implicit-migrations:generate {models?* : The model to generate migration for}';

    /** @var string */
    protected $description = 'Generate migration from models';

    /** @var array<string,string> */
    protected array $modelNames;

    /** array<string> */
    protected array $migrationPaths;

    protected MigrationGenerator $generator;

    public function handle()
    {
        $migrator = resolve('migrator');
        $this->migrationPaths = array_merge($migrator->paths(), [database_path(('migrations'))]);
        $this->modelNames = $this->argument('models') ?:
            $this->getModelNames(Config::get('database.model_paths'));

        $migrations = $this->getImplicitMigrations();
        $generator = new MigrationGenerator(static::TEMPLATE_NAME, $migrations);

        $migrationData = $generator->generate($this->modelNames);

        foreach ($migrationData as $tableName => $migrationItem) {
            $modelName = $migrationItem['modelName'];
            $reflection = new ReflectionClass($modelName);
            $modelFile = $reflection->getFileName();

            $migrationPath = $this->generateMigrationFilePath($tableName, $modelFile, $migrationItem['mode']);

            if (file_exists($migrationPath)) {
                echo "\tMigration file {$migrationPath} already exists. Skipping\n";
                continue;
            }

            file_put_contents($migrationPath, $migrationItem['contents']);
            echo "\tCreated migration: {$migrationPath}\n";
        }
    }

    protected function getModelNames($modelPaths)
    {
        $modelNames = [];
        $modelFiles = [];

        foreach ($modelPaths as $modelPath) {
            foreach (new FilesystemIterator(base_path($modelPath), FilesystemIterator::SKIP_DOTS) as $modelFile) {
                /** @var SplFileInfo $modelFile */
                require_once($modelFile->getRealPath());
                $modelFiles[] = $modelFile->getRealPath();
            }
        }

        foreach (get_declared_classes() as $className) {
            if (!is_subclass_of($className, Model::class, true)) {
                continue;
            }

            $modelFile = (new ReflectionClass($className))->getFileName();
            if (!in_array($modelFile, $modelFiles)) {
                continue;
            }

            $modelNames[$modelFile] = $className;
        }

        return $modelNames;
    }

    protected function getImplicitMigrations()
    {
        $implicitMigrations = [];

        foreach ($this->migrationPaths as $migrationPath) {
            $iterator = new FilesystemIterator($migrationPath, FilesystemIterator::SKIP_DOTS);
            foreach ($iterator as $migrationFile) {
                /** @var SplFileInfo $migrationFile */
                $fileName = $migrationFile->getRealPath();
                $migration = include($fileName);

                if (!$migration instanceof ImplicitMigration) {
                    continue;
                }

                $implicitMigrations[$fileName] = $migration;
            }
        }

        ksort($implicitMigrations, SORT_STRING);
        return $implicitMigrations;
    }

    protected function generateMigrationFilePath(string $tableName, string $modelFile, string $mode): string
    {
        static $nonce = 0;

        $fileName = date('Y_m_d_His') .
            '_' .
            $nonce++ .
            "_implicit_migration_{$mode}_{$tableName}_table.php";

        $targetPath = null;
        $modelPath = $modelFile;

        while (null === $targetPath && $modelPath) {
            $modelPath = substr($modelPath, 0, (int) strrpos($modelPath, DIRECTORY_SEPARATOR));
            $targetDepth = 0;

            foreach ($this->migrationPaths as $migrationPath) {
                if (0 !== strpos($migrationPath, $modelPath)) {
                    continue;
                }

                if (null === $targetPath || count(explode(DIRECTORY_SEPARATOR, $migrationPath)) < $targetDepth) {
                    $targetPath = $migrationPath;
                    $targetDepth = count(explode(DIRECTORY_SEPARATOR, $targetPath));
                }
            }
        }

        return $targetPath . DIRECTORY_SEPARATOR . $fileName;
    }
}
