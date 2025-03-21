<?php

namespace Toramanlis\ImplicitMigrations\Generator;

use Illuminate\Database\Schema\Blueprint;
use Toramanlis\ImplicitMigrations\Blueprint\BlueprintDiff;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableDiffExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Exporters\TableExporter;
use Toramanlis\ImplicitMigrations\Blueprint\Manager;
use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;

/** @package Toramanlis\ImplicitMigrations\Generator */
class MigrationGenerator
{
    /** @var array<string, Blueprint> */
    protected array $existingBlueprints = [];
    protected TemplateManager $templateManager;

    /**
     * @param string $templateName
     * @param array<ImplicitMigration> $existingMigrations
     */
    public function __construct(
        string $templateName,
        array $existingMigrations
    ) {
        $this->existingBlueprints = Manager::mergeMigrationsToBlueprints($existingMigrations);
        $this->templateManager = new TemplateManager($templateName);
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
                ImplicitMigration::MODE_UPDATE
            );
        }

        return $migrationData;
    }

    protected function getMigrationItem(
        string $source,
        Blueprint|BlueprintDiff $definition,
        string $mode = ImplicitMigration::MODE_CREATE
    ) {
        $modelName = explode('::', $source)[0];

        $exporter = match ($mode) {
            ImplicitMigration::MODE_CREATE => TableExporter::class,
            ImplicitMigration::MODE_UPDATE => TableDiffExporter::class,
        };

        [$tableNameOld, $tableNameNew] = $definition instanceof Blueprint
            ? [$definition->getTable(), $definition->getTable()]
            : [$definition->from->getTable(), $definition->to->getTable()];

        $tableNames = $tableNameOld === $tableNameNew
            ? (new TemplateManager('unchanged-table-name.php.tpl'))->process(['tableName' => $tableNameOld])
            : (new TemplateManager('different-table-names.php.tpl'))->process([
                'tableNameOld' => $tableNameOld,
                'tableNameNew' => $tableNameNew,
            ]);

        return [
            'modelName' => $modelName,
            'mode' => $mode,
            'contents' => $this->templateManager->process([
                'tableNames' => $tableNames,
                'migrationMode' => $mode,
                'source' => $source,
                'up' => $exporter::exportDefinition($definition),
                'down' => $exporter::exportDefinition($definition, TableExporter::MODE_DOWN),
            ]),
        ];
    }
}
