<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;
use Toramanlis\ImplicitMigrations\Attributes\IndexType;

class SimplifyingBlueprint extends Blueprint implements Migratable
{
    public function __construct($tableName, $prefix = '')
    {
        /** @var Connection */
        $connection = DB::connection();
        $connection->useDefaultSchemaGrammar();
        parent::__construct($connection, $prefix . $tableName);
    }

    public function applyColumnIndexes()
    {
        $applicables = [IndexType::Primary->value, IndexType::Unique->value, IndexType::Index->value];
        foreach ($this->commands as $command) {
            if (
                !in_array($command->name, $applicables) ||
                1 !== count($command->columns)
            ) {
                continue;
            }

            foreach ($this->columns as $column) {
                if ($column->name === $command->columns[0]) {
                    $defaultName = $this->createIndexName($command->name, $command->columns);
                    $column->{$command->name} = $command->index === $defaultName ? true : $command->index;
                    $this->dropIndex($command->index);
                    break;
                }
            }
        }
    }

    public function separateIndexesFromColumns()
    {
        foreach ($this->columns as $column) {
            foreach ([IndexType::Primary->value, IndexType::Unique->value, IndexType::Index->value] as $indexType) {
                if (!$column->$indexType) {
                    continue;
                }

                $this->$indexType($column->name, true === $column->$indexType ? null : $column->$indexType);
                unset($column->$indexType);
            }
        }
    }

    public function removeDuplicatePrimaries()
    {
        foreach ($this->commands as $command) {
            if (
                $command->name !== IndexType::Primary->value ||
                count($command->columns) !== 1
            ) {
                continue;
            }

            foreach ($this->columns as $column) {
                if ($column->name !== $command->columns[0]) {
                    continue;
                }

                if ($column->autoIncrement || $column->primary) {
                    $this->dropIndex($command->index);
                }
            }
        }
    }

    public function addColumn($type, $name, array $parameters = [])
    {
        parent::addColumn($type, $name, $parameters);

        foreach ($this->columns as $i => $column) {
            if ($column->name !== $name) {
                continue;
            }

            $newColumn = array_pop($this->columns);

            $this->columns[$i] = $newColumn;
            break;
        }

        return $newColumn;
    }

    public function dropColumn($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        $remainingColumns = [];

        foreach ($this->columns as $column) {
            if (in_array($column->name, $columns)) {
                continue;
            }

            $remainingColumns[] = $column;
        }

        $this->columns = $remainingColumns;

        return App::make(Fluent::class);
    }

    public function renameColumn($from, $to)
    {
        foreach ($this->columns as $column) {
            if ($column->name !== $from) {
                continue;
            }

            $column->name = $to;
            break;
        }

        return $column;
    }

    public function renameIndex($from, $to)
    {
        $this->separateIndexesFromColumns();

        foreach ($this->commands as $command) {
            if (null === IndexType::tryFrom($command->name) || $command->index !== $from) {
                continue;
            }

            $command->index = $to;
            break;
        }

        return $command;
    }

    protected function dropIndexCommand($command, $type, $index)
    {
        $remainingCommands = [];

        foreach ($this->commands as $command) {
            if (null !== IndexType::tryFrom($command->name) && $command->index === $index) {
                continue;
            }

            $remainingCommands[] = $command;
        }

        $this->commands = $remainingCommands;
        return $command;
    }

    public function getDependedColumnNames(): array
    {
        $dependedColumnNames = [];
        foreach ($this->commands as $command) {
            if (IndexType::Foreign->value !== $command->name) {
                continue;
            }

            $references = is_array($command->references) ? $command->references : [$command->references];
            foreach ($references as $reference) {
                $dependedColumnNames[] = "{$command->on}.{$reference}";
            }
        }

        return $dependedColumnNames;
    }

    public function getAddedColumnNames(): array
    {
        return array_map(fn ($column) => $column->name, $this->columns);
    }

    public function extractForeignKey(string $on, string $reference): Fluent
    {
        foreach ($this->commands as $command) {
            $references = is_array($command->references) ? $command->references : [$command->references];

            if (
                IndexType::Foreign->value !== $command->name ||
                $command->on !== $on ||
                !in_array($reference, $references)
            ) {
                continue;
            }

            $this->dropForeign($command->index);
            return $command;
        }

        throw new Exception("Reference {$on}.{$reference} has no foreign key in blueprint for {$this->getTable()}");
    }
}
