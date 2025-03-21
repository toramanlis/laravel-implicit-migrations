<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table('variations')]
class Variation extends Model
{
    public $variant_id;
    public $attribute_id;
    public $value;
}
