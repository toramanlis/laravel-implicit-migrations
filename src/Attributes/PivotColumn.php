<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Exception;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class PivotColumn extends Column
{
    public function __construct(?string $name, ...$attributes)
    {
        $this->name = $name;
        parent::__construct(...$attributes);
    }

    public function getName(): string
    {
        if (null === $this->name) {
            throw new Exception('Unable to get name before setting');
        }

        return $this->name;
    }

    public function inferFromReflectionProperty(ReflectionProperty $reflection): void
    {
    }
}
