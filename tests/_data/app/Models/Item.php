<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Primary;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
#[Primary(column: ['id'])]
class Item extends Model
{
    protected $table = 'order_items';
}
