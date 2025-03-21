<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Comment extends Model
{
    public $commentable_type;
    public $commentable_id;

    #[Relationship]
    public function commentable()
    {
        return $this->morphTo();
    }
}
