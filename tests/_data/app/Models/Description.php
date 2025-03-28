<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Index;
use Toramanlis\ImplicitMigrations\Attributes\Primary;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
#[Primary(['old_id', 'new_id'])]
#[Index(['old_id', 'product_id'])]
class Description extends Model
{
    public $describeable_type;
    public $describeable_id;
}
