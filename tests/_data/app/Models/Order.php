<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Relationship;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Order extends Model
{
    public $user_id;

    #[Relationship]
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
