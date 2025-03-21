<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\IndexDefinition;
use ReflectionProperty;
use Illuminate\Support\Str;
use Toramanlis\ImplicitMigrations\Blueprint\IndexType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class Unique extends Index
{
    protected ?array $columns;

    protected IndexType $type;

    public function __construct(
        protected ?string $name = null,
        null|array|string $column = null,
        protected ?string $algorithm = null,
        protected ?string $language = null
    ) {
        parent::__construct($name, 'unique', $column, $algorithm, $language);
    }
}
