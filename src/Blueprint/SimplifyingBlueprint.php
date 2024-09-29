<?php

namespace Toramanlis\ImplicitMigrations\Blueprint;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Fluent;

class SimplifyingBlueprint extends Blueprint
{
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

        return new Fluent();
    }

    public function renameColumn($from, $to)
    {
        foreach ($this->columns as $i => $column) {
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
            break;
        }

        $this->commands = $remainingCommands;
        return $command;
    }
}
