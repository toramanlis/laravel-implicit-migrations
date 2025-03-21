<?php

namespace Toramanlist\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Redirection extends Model
{
    public string $from;
    public string $to;
}
