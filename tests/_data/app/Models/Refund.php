<?php

namespace Toramanlist\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Index;

#[Index(column: 'order_id')]
class Refund extends Model
{
}
