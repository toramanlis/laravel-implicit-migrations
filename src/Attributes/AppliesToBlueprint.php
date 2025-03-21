<?php

namespace Toramanlis\ImplicitMigrations\Attributes;

use Illuminate\Database\Schema\Blueprint;

interface AppliesToBlueprint
{
    public function applyToBlueprint(Blueprint $table): Blueprint;
}
