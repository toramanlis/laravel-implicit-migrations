<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Reaction extends Model
{
    #[Relationship]
    public function comments()
    {
        return $this->morphedByMany(Comment::class, 'reactable');
    }

    #[Relationship]
    public function variations()
    {
        return $this->morphedByMany(Variation::class, 'reactable');
    }
}
