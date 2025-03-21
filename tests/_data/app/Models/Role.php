<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Role extends Model
{
    #[Relationship]
    public function user()
    {
        return $this->hasMany(User::class);
    }
}
