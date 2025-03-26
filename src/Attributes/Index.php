<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\IndexDefinition;
use ReflectionProperty;
use Illuminate\Support\Str;
use Toramanlis\ImplicitMigrations\Blueprint\IndexType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Index extends MigrationAttribute
{
    protected ?array $columns;

    protected IndexType $type;

    public function __construct(
        protected ?string $name = null,
        string $type = 'index',
        null|array|string $column = null,
        protected ?string $algorithm = null,
        protected ?string $language = null
    ) {
        $this->type = IndexType::from(strtolower($type));
        $this->columns = is_string($column) ? [$column] : $column;
    }

    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
        if (null !== $this->columns) {
            return; // @codeCoverageIgnore
        }

        $this->columns = [Str::snake($reflection->getName())];
    }

    public function ensureColumns(Blueprint $table): void
    {
        if (is_null($this->columns)) {
            return; // @codeCoverageIgnore
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

    public function applyToBlueprint(Blueprint $table): Blueprint
    {
        /** @var IndexDefinition $index */
        $index = $table->{$this->type->value}($this->columns, $this->name);
        $index->algorithm($this->algorithm);
        $index->language($this->language);

        return $table;
    }
}
