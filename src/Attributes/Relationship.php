<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Schema\Blueprint;

#[Attribute(Attribute::TARGET_METHOD)]
class Relationship extends MigrationAttribute
{
    public function applyToBlueprint(Blueprint $table): Blueprint
    {
        return $table;
    }
}
