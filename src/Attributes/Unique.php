<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Toramanlis\ImplicitMigrations\Blueprint\IndexType;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
class Unique extends Index
{
    protected ?array $columns;

    protected IndexType $type;

    public function __construct(
        null|array|string $column = null,
        protected ?string $name = null,
        protected ?string $algorithm = null,
        protected ?string $language = null
    ) {
        parent::__construct($column, 'unique', $name, $algorithm, $language);
    }
}
