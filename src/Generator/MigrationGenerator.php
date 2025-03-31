<?php

namespace Toramanlis\ImplicitMigrations\Generator;

use Illuminate\Database\Schema\Blueprint;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableDiffExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Manager;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\App;
use Toramanlis\ImplicitMigrations\Blueprint\BlueprintDiff;
use Toramanlis\ImplicitMigrations\Blueprint\Migratable;

/** @package Toramanlis\ImplicitMigrations\Generator */
class MigrationGenerator
{
    protected const CREATE_TEMPLATE = 'migration-create.php.tpl';
    protected const UPDATE_TEMPLATE = 'migration-update.php.tpl';

    /** @var array<string, SimplifyingBlueprint> */
    protected array $existingBlueprints = [];
    protected TemplateManager $createTemplateManager;
    protected TemplateManager $updateTemplateManager;

    /**
     * @param string $templateName
     * @param array<Migration> $existingMigrations
     */
    public function __construct(array $existingMigrations)
    {
        $this->existingBlueprints = Manager::mergeMigrationsToBlueprints($existingMigrations);

        /** @var TemplateManager */
        $manager = App::make(TemplateManager::class, ['templateName' => static::CREATE_TEMPLATE]);
        $this->createTemplateManager = $manager;
        /** @var TemplateManager */
        $manager = App::make(TemplateManager::class, ['templateName' => static::UPDATE_TEMPLATE]);
        $this->updateTemplateManager = $manager;
    }

    /**
     * @param array<string> $modelNames
     * @return array<string,array<string,string>>
     */
    public function generate(array $modelNames): array
    {
        $migrationData = [];
        $blueprints = [];
        $relationships = [];
        $sourceMap = [];

        foreach ($modelNames as $modelName) {
            $modelRelationships = Manager::getRelationships($modelName);

            $relationships = array_merge($relationships, $modelRelationships);

            $blueprint = Manager::generateBlueprint($modelName);

            if (null === $blueprint) {
                continue;
            }

            $blueprints[$blueprint->getTable()] = $blueprint;
            $sourceMap[$blueprint->getTable()] = $modelName;
        }

        /** @var Manager */
        $blueprintManager = App::make(Manager::class, ['blueprints' => $blueprints]);
        $blueprintManager->applyRelationshipsToBlueprints($relationships);
        $blueprintManager->ensureIndexColumns($modelNames);

        foreach ($blueprintManager->getRelationshipMap() as $tableName => $relationship) {
            $sourceMap[$tableName] = $sourceMap[$tableName] ?? $relationship->getSource();
        }

        $migratables = [];
        foreach ($blueprintManager->getBlueprints() as $table => $blueprint) {
            $blueprint->removeDuplicatePrimaries();
            $source = $sourceMap[$table];
            if (!isset($this->existingBlueprints[$source])) {
                $migratables[$table] = $blueprint;
                continue;
            }

            $diff = Manager::getDiff($this->existingBlueprints[$source], $blueprint);

            if ($diff->none()) {
                continue;
            }

            $migratables[$table] = $diff;
        }

        $migratables = $this->sortMigrations($migratables);

        foreach ($migratables as $table => $migratable) {
            $source = $sourceMap[ltrim($table, '_')];

            if ($migratable instanceof SimplifyingBlueprint) {
                $migrationData[$table] = $this->getMigrationItem($source, $migratable);
            } else {
                /** @var BlueprintDiff $migratable */
                $migratable->applyColumnIndexes();
                $migratable->applyColumnIndexes(true);
                $key = isset($this->existingBlueprints[$source])
                    ? $this->existingBlueprints[$source]->getTable() : $table;
                $migrationData[$key] = $this->getMigrationItem(
                    $source,
                    $migratable,
                );
            }
        }

        return $migrationData;
    }

    /**
     * @param array<Migratable> $migratables
     * @return array<Migratable> $migratables
     */
    protected function sortMigrations(array $migratables): array
    {
        $extraMigratables = $this->separateCodependents($migratables);

        $sorted = [];

        while (count($migratables)) {
            $dependencyMap = $this->getDependencyMap($migratables);

            foreach (array_keys($migratables) as $table) {
                $dependencies = $dependencyMap[$table] ?? [];

                $counts = array_map(fn ($item) => count($item), $dependencies);
                if (0 !== array_sum($counts)) {
                    continue;
                }

                $sorted[$table] = $migratables[$table];
                unset($migratables[$table]);
            }
        }

        return array_merge($sorted, $extraMigratables);
    }

    protected function getDependencyMap($migratables)
    {
        $addedColumns = [];

        foreach ($migratables as $table => $migratable) {
            foreach ($migratable->getAddedColumnNames() as $addedColumn) {
                $addedColumns["{$table}.{$addedColumn}"] = $table;
            }
        }

        $dependencyMap = [];
        foreach (array_keys($migratables) as $table) {
            $dependencyMap[$table] = $this->getDependencies($table, $migratables, $addedColumns);
        }

        return $dependencyMap;
    }


    protected function separateCodependents($migratables): array
    {
        $extraMigratables = [];

        while (count($codependents = $this->getCodependents($migratables))) {
            $biggestDependent = null;
            $mostDependencies = 0;

            foreach ($codependents as $table => $dependencies) {
                foreach ($dependencies as $column => $columnDependencies) {
                    if (count($columnDependencies) <= $mostDependencies) {
                        continue;
                    }

                    $mostDependencies = count($columnDependencies);
                    $biggestDependent = ['table' => $table, 'column' => $column];
                }
            }

            [$on, $shortColumn] = explode('.', $biggestDependent['column']);
            $extraMigratable = App::make(BlueprintDiff::class, [
                'from' => App::make(SimplifyingBlueprint::class, ['tableName' => $biggestDependent['table']]),
                'to' => App::make(SimplifyingBlueprint::class, ['tableName' => $biggestDependent['table']]),
                'modifiedColumns' => [],
                'droppedColumns' => [],
                'addedColumns' => [],
                'droppedIndexes' => [],
                'renamedIndexes' => [],
                'addedIndexes' => [],
                'addedIndexes' => [$migratables[$biggestDependent['table']]->extractForeignKey($on, $shortColumn)],
            ]);
            $extraMigratables['_' . $biggestDependent['table']] = $extraMigratable;
        }


        return $extraMigratables;
    }

    protected function getCodependents($migratables)
    {
        $dependencyMap = $this->getDependencyMap($migratables);

        $codependents = [];
        foreach ($dependencyMap as $table => $dependencies) {
            foreach ($dependencies as $dependedColumn => $columnDependencies) {
                foreach ($columnDependencies as $dependency) {
                    foreach ($dependencyMap[$dependency] as $counterColumn => $subdependencies) {
                        if (!in_array($table, $subdependencies)) {
                            continue;
                        }

                        $codependents[$table] ??= [];
                        $codependents[$table][$dependedColumn] ??= [];
                        if (!in_array($dependency, $codependents[$table][$dependedColumn])) {
                            $codependents[$table][$dependedColumn][] = $dependency;
                        }

                        $codependents[$dependency] ??= [];
                        $codependents[$dependency][$counterColumn] ??= [];
                        if (!in_array($table, $codependents[$dependency][$counterColumn])) {
                            $codependents[$dependency][$counterColumn][] = $table;
                        }
                    }
                }
            }
        }

        return $codependents;
    }

    protected function getDependencies($table, $migratables, $addedColumns, $chain = []): array
    {
        if (in_array($table, $chain)) {
            return [];
        }

        $chain = array_merge($chain, [$table]);

        $dependencies = [];
        $migratable = $migratables[$table];
        foreach ($migratable->getDependedColumnNames() as $dependedColumn) {
            if (!isset($addedColumns[$dependedColumn])) {
                continue;
            }

            $dependency = $addedColumns[$dependedColumn];
            $subDependencies = $this->getDependencies($dependency, $migratables, $addedColumns, $chain);
            $merged = array_unique(array_merge([$dependency], $subDependencies));

            if (1 === count($chain)) {
                $dependencies[$dependedColumn] = $merged;
            } else {
                $dependencies = $merged;
            }
        }

        return $dependencies;
    }

    protected function getMigrationItem(
        string $source,
        Migratable $definition,
    ) {
        $modelName = explode('::', $source)[0];
        $mode = $definition instanceof SimplifyingBlueprint ? MigrationMode::Create : MigrationMode::Update;

        $exporter = match ($mode) {
            MigrationMode::Create => TableExporter::class,
            MigrationMode::Update => TableDiffExporter::class,
        };

        $templateManager = match ($mode) {
            MigrationMode::Create => $this->createTemplateManager,
            MigrationMode::Update => $this->updateTemplateManager,
        };

        [$tableNameOld, $tableNameNew] = $definition instanceof Blueprint
            ? [$definition->getTable(), $definition->getTable()]
            : [$definition->from->getTable(), $definition->to->getTable()];

        $tableRenamed = $tableNameNew !== $tableNameOld;
        [$sourceClass, $sourceMethod] = explode('::', $source . '::');

        return [
            'modelName' => $modelName,
            'mode' => $mode->value,
            'contents' => $templateManager->process([
                'sourceClass' => $sourceClass,
                'source' => $sourceMethod ? "Source::class . '::{$sourceMethod}'" : 'Source::class',
                'tableNameNew' => $tableNameNew,
                'tableNameOld' => $tableRenamed ? "'{$tableNameOld}'" : 'static::TABLE_NAME',
                'up' => $exporter::exportDefinition($definition),
                'down' => $exporter::exportDefinition($definition, TableExporter::MODE_DOWN),
            ]),
        ];
    }
}
