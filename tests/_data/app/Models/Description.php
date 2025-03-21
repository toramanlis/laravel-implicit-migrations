<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Description extends Model
{
    public $describeable_type;
    public $describeable_id;
}
