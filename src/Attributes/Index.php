<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\IndexDefinition;
use ReflectionProperty;
use Illuminate\Support\Str;
use Toramanlis\ImplicitMigrations\Blueprint\IndexType;
use Toramanlis\ImplicitMigrations\Exceptions\ImplicationException;
use ValueError;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Index extends MigrationAttribute
{
    protected ?array $columns;

    protected IndexType $type;

    public function __construct(
        null|array|string $column = null,
        string $type = 'index',
        protected ?string $name = null,
        protected ?string $algorithm = null,
        protected ?string $language = null,
    ) {
        try {
            $this->type = IndexType::from(strtolower($type));
        } catch (ValueError $e) {
            throw new ImplicationException(ImplicationException::CODE_IDX_NO_TYPE, [$type], $e);
        }

        $this->columns = is_string($column) ? [$column] : $column;
    }

    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
        if (null !== $this->columns) {
            return;
        }

        $this->columns = [Str::snake($reflection->getName())];
    }

    public function ensureColumns(Blueprint $table): void
    {
        if (is_null($this->columns)) {
            return;
        }

        $existingColumns = array_map(fn ($column) => $column->name, $table->getColumns());
        $missingColumns = [];
        foreach ($this->columns as $column) {
            if (!in_array($column, $existingColumns)) {
                $missingColumns[] = $column;
            }
        }

        if (empty($missingColumns)) {
            return;
        }

        foreach ($missingColumns as $column) {
            $table->string($column);
        }
    }

    protected function validate(Blueprint $table)
    {
        if (empty($this->columns)) {
            throw new ImplicationException(
                ImplicationException::CODE_IDX_NO_COL,
                [$table->getTable(), $this->type->name]
            );
        }
    }

    public function applyToBlueprint(Blueprint $table): Blueprint
    {
        $this->validate($table);

        /** @var IndexDefinition $index */
        $index = $table->{$this->type->value}($this->columns, $this->name);
        $index->algorithm($this->algorithm);
        $index->language($this->language);

        return $table;
    }
}
