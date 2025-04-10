<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class PivotColumn extends Column
{
    public function __construct(
        ?string $name,
        protected ?string $type = null,
        protected ?bool $nullable = null,
        protected $default = null,
        protected ?int $length = null,
        protected ?bool $unsigned = null,
        protected ?bool $autoIncrement = null,
        protected ?int $precision = null,
        protected ?int $total = null,
        protected ?int $places = null,
        protected ?array $allowed = null,
        protected ?bool $fixed = null,
        protected ?string $subtype = null,
        protected ?int $srid = null,
        protected ?string $expression = null,
        protected ?string $collation = null,
        protected ?string $comment = null,
        protected ?string $virtualAs = null,
        protected ?string $storedAs = null,
        protected ?string $after = null
    ) {
        parent::__construct(
            $type,
            $name,
            $nullable,
            $default,
            $length,
            $unsigned,
            $autoIncrement,
            $precision,
            $total,
            $places,
            $allowed,
            $fixed,
            $subtype,
            $srid,
            $expression,
            $collation,
            $comment,
            $virtualAs,
            $storedAs,
            $after
        );
    }

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
        return;
    }
}
