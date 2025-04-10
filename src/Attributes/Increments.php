<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Increments extends UnsignedInteger
{
    public function __construct(
        protected ?string $name = null,
        protected ?bool $nullable = null,
        protected $default = null,
        protected ?string $comment = null,
        protected ?string $virtualAs = null,
        protected ?string $storedAs = null,
        protected ?string $after = null
    ) {
        parent::__construct(
            $name,
            $nullable,
            $default,
            autoIncrement: true,
            comment: $comment,
            virtualAs: $virtualAs,
            storedAs: $storedAs,
            after: $after
        );
    }
}
