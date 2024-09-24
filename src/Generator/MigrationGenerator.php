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
        $tableModelMap = [];
        $blueprints = [];

        foreach ($modelNames as $modelName) {
            $modelBlueprints = Manager::generateBlueprints($modelName);

            foreach ($modelBlueprints as $source => $blueprint) {
                if (null === $blueprint) {
                    continue;
                }

                $tableModelMap[$blueprint->getTable()] = $modelName;
                $blueprints[$source] = $blueprint;
            }
        }

        foreach ($blueprints as $source => $blueprint) {
            if (!isset($this->existingBlueprints[$source])) {
                $migrationData[$blueprint->getTable()] = $this->getMigrationItem($source, $blueprint);
                continue;
            }

            $diff = Manager::getDiff($this->existingBlueprints[$source], $blueprint);

            if ($diff->none()) {
                continue;
            }

            $migrationData[$blueprint->getTable()] = $this->getMigrationItem(
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

        return [
            'modelName' => $modelName,
            'mode' => $mode,
            'contents' => $this->templateManager->process([
                'tableNameOld' => $tableNameOld,
                'tableNameNew' => $tableNameNew,
                'source' => $source,
                'migrationMode' => $mode,
                'up' => $exporter::exportDefinition($definition),
                'down' => $exporter::exportDefinition($definition, TableExporter::MODE_DOWN),
            ]),
        ];
    }
}
