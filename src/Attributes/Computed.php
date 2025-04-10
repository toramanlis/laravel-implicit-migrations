<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Computed extends ColumnAlias
{
    protected const TYPE = 'computed';

    public function __construct(
        protected ?string $name = null,
        protected ?bool $nullable = null,
        protected $default = null,
        protected ?string $expression = null,
        protected ?string $comment = null,
        protected ?string $virtualAs = null,
        protected ?string $storedAs = null,
        protected ?string $after = null
    ) {
        parent::__construct(
            static::TYPE,
            $name,
            $nullable,
            $default,
            expression: $expression,
            comment: $comment,
            virtualAs: $virtualAs,
            storedAs: $storedAs,
            after: $after
        );
    }
}
