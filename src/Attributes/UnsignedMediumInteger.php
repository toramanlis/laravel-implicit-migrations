<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class UnsignedMediumInteger extends UnsignedInteger
{
    protected const TYPE = 'mediumInteger';
}
