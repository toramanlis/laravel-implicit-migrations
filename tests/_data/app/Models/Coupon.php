<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Coupon extends Model
{
    public $user_id;

    public $promotion_id;
}
