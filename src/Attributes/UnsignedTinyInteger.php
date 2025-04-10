<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class UnsignedTinyInteger extends UnsignedInteger
{
    protected const TYPE = 'tinyInteger';
}
