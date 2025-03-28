<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Primary;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
#[Primary(column: ['id'])]
class Redirection extends Model
{
    protected $keyType = 'none';

    public string $from;
    public string $to;
}
