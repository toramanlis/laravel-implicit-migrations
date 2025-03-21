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
        protected ?string $name = null,
        null|array|string $column = null,
        protected ?string $algorithm = null,
        protected ?string $language = null
    ) {
        parent::__construct($name, 'unique', $column, $algorithm, $language);
    }
}
