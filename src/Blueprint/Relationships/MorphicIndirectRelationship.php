<?php

namespace Toramanlis\ImplicitMigrations\Blueprint\Relationships;

use Illuminate\Support\Str;

class MorphicIndirectRelationship extends IndirectRelationship
{
    use Polymorphic;

    public function getForeignKeyAliases(): array
    {
        return array_reduce(
            $this->getRelatedTables(),
            function ($carry, $table) {
                $carry[$table] = Str::singular($table) . '_' . $this->getLocalKeys()[$table];
                return $carry;
            },
            []
        );
    }
}
