<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Category extends Model
{
    #[Relationship]
    public function description()
    {
        return $this->morphOne(Description::class, 'describeable');
    }

    #[Relationship]
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
