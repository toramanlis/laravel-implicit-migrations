<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

abstract class ColumnAlias extends Column
{
    protected const TYPE = null;

    public function __construct(
        protected ?string $name = null,
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
            static::TYPE,
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
}
