<?php

namespace Toramanlis\ImplicitMigrations\Generator;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Toramanlis\ImplicitMigrations\Exporters\TableDiffExporter;
use Toramanlis\ImplicitMigrations\Exporters\TableExporter;
use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;

/** @package Toramanlis\ImplicitMigrations\Generator */
class MigrationGenerator
{
    public const MIGRATION_MODE_CREATE = 'create';
    public const MIGRATION_MODE_UPDATE = 'update';

    protected TemplateManager $templateManager;

    /**
     * @param string $templateName
     * @param array<ImplicitMigration> $existingMigrations
     */
    public function __construct(
        string $templateName,
        protected array $existingMigrations
    ) {
        $this->templateManager = new TemplateManager($templateName);
    }

    protected function prepareExistingTable($tableName)
    {
        $table = null;

        foreach ($this->existingMigrations as $migration) {
            if ($migration->getTableName() !== $tableName) {
                continue;
            }

            if (null === $table) {
                $table = new Table($tableName);
            }

            $migration->tableUp($table);
        }

        return $table;
    }

    public function generate(string $modelName): ?string
    {
        $dbParams = ImplicitMigration::getDbParams();

        $config = ORMSetup::createAttributeMetadataConfiguration([]);
        $connection = DriverManager::getConnection($dbParams, $config);
        $entityManager = new EntityManager($connection, $config);
        $schemaManager = $connection->createSchemaManager();
        $comparator = $schemaManager->createComparator();

        $schemaTool = new SchemaTool($entityManager);

        try {
            $modelMetadata = $entityManager->getClassMetadata($modelName);
        } catch (MappingException $e) {
            echo "\tModel {$modelName} skipped\n";
            return null;
        }

        $schema = $schemaTool->getSchemaFromMetadata([$modelMetadata]);
        $tableName = $modelMetadata->getTableName();

        $inferredTable = $schema->getTable($tableName);
        $existingTable = $this->prepareExistingTable($tableName);

        if (null === $existingTable) {
            $exporter = new TableExporter($inferredTable);

            $mode = ImplicitMigration::MODE_CREATE;
            $up = $exporter->export();
            $down = TableExporter::exportAsset($inferredTable, TableExporter::MODE_DROP);
        } else {
            $forward = $comparator->compareTables($existingTable, $inferredTable);

            if ($forward->isEmpty()) {
                return null;
            }

            $backward = $comparator->compareTables($inferredTable, $existingTable);

            $mode = ImplicitMigration::MODE_UPDATE;
            $up = TableDiffExporter::exportAsset($forward);
            $down = TableDiffExporter::exportAsset($backward);
        }


        return $this->templateManager->process([
            'tableName' => $tableName,
            'migrationMode' => $mode,
            'up' => $up,
            'down' => $down,
        ]);
    }
}
