<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

use Illuminate\Support\Str;

class MorphicDirectRelationship extends DirectRelationship
{
    use Polymorphic;
}
