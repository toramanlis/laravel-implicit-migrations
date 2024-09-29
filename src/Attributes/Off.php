<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Attribute;
use Illuminate\Database\Schema\Blueprint;

#[Attribute]
class Off extends MigrationAttribute
{
    public function applyToBlueprint(Blueprint $table): Blueprint
    {
        return $table;
    }
}
