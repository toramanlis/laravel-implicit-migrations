<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Primary;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table('variations')]
#[Primary(column: ['id'])]
class Variation extends Model
{
    protected $keyType = 'string';

    public $variant_id;
    public $attribute_id;
    public $value;
}
