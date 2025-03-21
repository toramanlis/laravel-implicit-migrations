<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

use Illuminate\Support\Str;

class MorphicDirectRelationship extends DirectRelationship
{
    use Polymorphic;

    public function getForeignKeyAlias(): string
    {
        return Str::singular($this->getParentTable()) . '_' . $this->getLocalKey();
    }
}
