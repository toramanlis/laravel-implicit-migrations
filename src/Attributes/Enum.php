<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Enum extends ColumnAlias
{
    protected const TYPE = 'enum';

    public function __construct(
        protected ?string $name = null,
        protected ?bool $nullable = null,
        protected $default = null,
        protected ?array $allowed = null,
        protected ?string $collation = null,
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
            allowed: $allowed,
            collation: $collation,
            comment: $comment,
            virtualAs: $virtualAs,
            storedAs: $storedAs,
            after: $after
        );
    }
}
