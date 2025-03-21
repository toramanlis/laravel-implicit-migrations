<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Profile extends Model
{
    #[Relationship]
    public function store()
    {
        return $this->hasOneThrough(Store::class, User::class);
    }

    #[Relationship]
    public function orders()
    {
        return $this->hasManyThrough(Coupon::class, User::class);
    }
}
