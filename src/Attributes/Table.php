<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS)]
class Table extends MigrationAttribute
{
    public function __construct(
        public ?string $name = null,
        public string $prefix = '',
        protected ?string $engine = null,
        protected ?string $charset = null,
        protected ?string $collation = null
    ) {
    }

    public function inferFromReflectionClass(ReflectionClass $reflection): void
    {
        if (null !== $this->name) {
            return;
        }

        $modelClass = $reflection->getName();

        /** @var Model */
        $model = new $modelClass();
        $this->name = $model->getTable();
    }

    public function applyToBlueprint(Blueprint $table): Blueprint
    {
        $table->engine($this->engine);
        $table->charset($this->charset);
        $table->collation($this->collation);

        return $table;
    }
}
