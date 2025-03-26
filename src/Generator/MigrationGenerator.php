<?php

namespace Toramanlis\ImplicitMigrations\Generator;

use Illuminate\Database\Schema\Blueprint;
use Toramanlis\ImplicitMigrations\Blueprint\BlueprintDiff;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableDiffExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Manager;
use Toramanlis\ImplicitMigrations\Blueprint\SimplifyingBlueprint;
use Illuminate\Database\Migrations\Migration;

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

        $this->createTemplateManager = new TemplateManager(static::CREATE_TEMPLATE);
        $this->updateTemplateManager = new TemplateManager(static::UPDATE_TEMPLATE);
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
                continue; // @codeCoverageIgnore
            }

            $blueprints[$blueprint->getTable()] = $blueprint;
            $sourceMap[$blueprint->getTable()] = $modelName;
        }

        $blueprintManager = new Manager($blueprints);
        $blueprintManager->applyRelationshipsToBlueprints($relationships);
        $blueprintManager->ensureIndexColumns($modelNames);

        foreach ($blueprintManager->getRelationshipMap() as $tableName => $relationship) {
            $sourceMap[$tableName] = $sourceMap[$tableName] ?? $relationship->getSource();
        }

        foreach ($blueprintManager->getBlueprints() as $table => $blueprint) {
            $blueprint->removeDuplicatePrimaries();
            $source = $sourceMap[$table];
            if (!isset($this->existingBlueprints[$source])) {
                $migrationData[$table] = $this->getMigrationItem($source, $blueprint);
                continue;
            }

            $diff = Manager::getDiff($this->existingBlueprints[$source], $blueprint);

            if ($diff->none()) {
                continue;
            }

            $migrationData[$this->existingBlueprints[$source]->getTable()] = $this->getMigrationItem(
                $source,
                $diff,
                MigrationMode::Update
            );
        }

        return $migrationData;
    }

    protected function getMigrationItem(
        string $source,
        Blueprint|BlueprintDiff $definition,
        MigrationMode $mode = MigrationMode::Create
    ) {
        $modelName = explode('::', $source)[0];

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
