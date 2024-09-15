<?php

namespace Toramanlis\ImplicitMigrations\Generator;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Toramanlis\ImplicitMigrations\Exporters\TableDiffExporter;
use Toramanlis\ImplicitMigrations\Exporters\TableExporter;
use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;

/** @package Toramanlis\ImplicitMigrations\Generator */
class MigrationGenerator
{
    protected EntityManager $entityManager;
    
    protected Comparator $comparator;
    
    protected SchemaTool $schemaTool;
    
    protected TemplateManager $templateManager;
    
    /**
     * @param string $templateName
     * @param array<ImplicitMigration> $existingMigrations
     */
    public function __construct(
        string $templateName,
        protected array $existingMigrations
    ) {
        $dbParams = ImplicitMigration::getDbParams();
        
        $config = ORMSetup::createAttributeMetadataConfiguration([]);
        $connection = DriverManager::getConnection($dbParams, $config);
        $schemaManager = $connection->createSchemaManager();
        
        $this->entityManager = new EntityManager($connection, $config);
        $this->comparator = $schemaManager->createComparator();
        $this->schemaTool = new SchemaTool($this->entityManager);
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

    /**
     * @param array<string> $modelNames 
     * @return array<string,array<string,string>>
     */
    public function generate(array $modelNames): array
    {
        $skippedModels = [];
        $modelsMetadata = [];
        $tableModelMap = [];
        
        foreach ($modelNames as $modelName) {
            try {
                $singleModelMetadata = $this->entityManager->getClassMetadata($modelName);
                $tableModelMap[$singleModelMetadata->getTableName()] = $modelName;

                foreach ($singleModelMetadata->getAssociationMappings() as $mapping) {
                    if ($mapping instanceof ManyToManyOwningSideMapping) {
                        $tableModelMap[$mapping->joinTable->name] = $modelName;
                    }
                }
                $modelsMetadata[] = $singleModelMetadata;
            } catch (MappingException $e) {
                $skippedModels[] = $modelName;
            }
        }
        
        $schema = $this->schemaTool->getSchemaFromMetadata($modelsMetadata);
        $migrationData = [];

        foreach ($schema->getTables() as $table) {
            $migrationItem = $this->generateTableMigration($table);
            $tableName = $table->getName();

            if (null === $migrationItem) {
                continue;
            }

            $migrationItem['modelName'] = $tableModelMap[$tableName];
            $migrationData[$tableName] = $migrationItem;
        }

        return $migrationData;
    }

    protected function generateTableMigration(Table $inferredTable): ?array
    {
        $tableName = $inferredTable->getName();
        $existingTable = $this->prepareExistingTable($tableName);

        if (null === $existingTable) {
            $exporter = new TableExporter($inferredTable);

            $mode = ImplicitMigration::MODE_CREATE;
            $up = $exporter->export();
            $down = TableExporter::exportAsset($inferredTable, TableExporter::MODE_DROP);
        } else {
            $forward = $this->comparator->compareTables($existingTable, $inferredTable);

            if ($forward->isEmpty()) {
                return null;
            }

            $backward = $this->comparator->compareTables($inferredTable, $existingTable);

            $mode = ImplicitMigration::MODE_UPDATE;
            $up = TableDiffExporter::exportAsset($forward);
            $down = TableDiffExporter::exportAsset($backward);
        }

        $contents = $this->templateManager->process([
            'tableName' => $tableName,
            'migrationMode' => $mode,
            'up' => $up,
            'down' => $down,
        ]);

        return [
            'mode' => $mode,
            'contents' => $contents,
        ];
    }
}
