<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\IndexDefinition;
use ReflectionProperty;
use Illuminate\Support\Str;
use Toramanlis\ImplicitMigrations\Blueprint\IndexType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Primary extends Index
{
    public function __construct(
        protected ?string $name = null,
        null|array|string $column = null,
        protected ?string $algorithm = null,
        protected ?string $language = null
    ) {
        parent::__construct($name, 'primary', $column, $algorithm, $language);
    }
}
