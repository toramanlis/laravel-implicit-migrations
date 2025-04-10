<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Binary extends ColumnAlias
{
    protected const TYPE = 'binary';

    public function __construct(
        protected ?string $name = null,
        protected ?bool $nullable = null,
        protected $default = null,
        protected ?bool $fixed = null,
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
            fixed: $fixed,
            comment: $comment,
            virtualAs: $virtualAs,
            storedAs: $storedAs,
            after: $after
        );
    }
}
