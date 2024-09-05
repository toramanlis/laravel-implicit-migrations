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
use Toramanlis\ImplicitMigrations\Migration\ImplicitMigration;

/** @package Toramanlis\ImplicitMigrations\Generator */
class MigrationGenerator
{
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

    public function generate(string $modelName)
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
        $other = $schema->getTable($modelMetadata->getTableName());

        $existingTable = $this->prepareExistingTable($tableName);

        if (null === $existingTable) {
            $exporter = new TableExporter($other);

            $up = $exporter->export();
            $down = $exporter->export(TableExporter::MODE_DROP);
        } else {
            $forward = $comparator->compareTables($existingTable, $other);
            $backward = $comparator->compareTables($other, $existingTable);

            $up = TableDiffExporter::exportAsset($forward);
            $down = TableDiffExporter::exportAsset($backward);
        }


        return $this->templateManager->process([
            'tableName' => $tableName,
            'up' => $up,
            'down' => $down,
        ]);
    }
}
