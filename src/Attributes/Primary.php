<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Primary extends Index
{
    public function __construct(
        null|array|string $column = null,
        protected ?string $name = null,
        protected ?string $algorithm = null,
        protected ?string $language = null
    ) {
        parent::__construct($column, 'primary', $name, $algorithm, $language);
    }
}
