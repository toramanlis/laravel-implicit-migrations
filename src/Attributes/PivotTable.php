<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PivotTable extends Table
{
}
