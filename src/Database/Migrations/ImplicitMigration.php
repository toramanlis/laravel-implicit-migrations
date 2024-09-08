<?php

namespace Toramanlis\ImplicitMigrations\Database\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;

abstract class ImplicitMigration extends Migration
{
    public const MODE_CREATE = 'create';
    public const MODE_UPDATE = 'update';

    protected const TABLE_NAME = '';

    protected const DRIVER_MAP = [
        'mysql' => 'pdo_mysql',
        'sqlite' => 'pdo_sqlite',
        'pgsql' => 'pdo_pgsql',
        'sqlsrv' => 'pdo_sqlsrv',
    ];

    protected static $mode = '';

    protected Connection $connectionInterface;

    protected AbstractSchemaManager $schemaManager;

    protected AbstractPlatform $platform;

    abstract public function tableUp(Table $table): void;

    abstract public function tableDown(Table $table): void;

    public function __construct()
    {
        $dbParams = static::getDbParams();

        $this->connectionInterface = DriverManager::getConnection($dbParams);
        $this->schemaManager = $this->connectionInterface->createSchemaManager();
        $this->platform = $this->connectionInterface->getDatabasePlatform();
    }

    public static function getDbParams()
    {
        $config = Config::get('database.connections.' . Config::get('database.default'));

        return [
            ...$config,
            'driver'   => static::DRIVER_MAP[$config['driver']],
            'user'     => $config['username'],
            'password' => $config['password'],
            'dbname'   => $config['database'],
            'host'     => $config['host'],
            'port'     => $config['port']
        ];
    }

    public function getTableName()
    {
        return static::TABLE_NAME;
    }

    /**
     * @param array<string> $statements
     * @return void
     */
    protected function executeStatements(array $statements)
    {
        foreach ($statements as $statement) {
            $this->connectionInterface->executeStatement($statement);
        }
    }

    protected function applyDifference(Table $existingTable, Table $alteredTable)
    {
        $comparator = $this->schemaManager->createComparator();
        $tableDiff = $comparator->compareTables($existingTable, $alteredTable);

        $this->executeStatements($this->platform->getAlterTableSQL($tableDiff));
    }

    public function up()
    {
        if (static::MODE_CREATE === static::$mode) {
            $createdTable = new Table(static::TABLE_NAME);
            $this->tableUp($createdTable);
            $this->executeStatements($this->platform->getCreateTableSQL($createdTable));
        } else {
            $existingTable = $this->schemaManager->introspectTable(static::TABLE_NAME);
            $alteredTable = clone $existingTable;
            $this->tableUp($alteredTable);

            $this->applyDifference($existingTable, $alteredTable);
        }
    }

    public function down()
    {
        $existingTable = $this->schemaManager->introspectTable(static::TABLE_NAME);
        $revertedTable = clone $existingTable;

        $this->tableDown($revertedTable);
        $this->applyDifference($existingTable, $revertedTable);
    }

    protected function dropTable()
    {
        $this->connectionInterface->executeStatement($this->platform->getDropTableSQL(static::TABLE_NAME));
    }
}
