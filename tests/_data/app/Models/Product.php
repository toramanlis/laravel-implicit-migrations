<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\Index;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table]
class Product extends Model
{
    protected $primaryKey = 'p_id';

    #[Column]
    public string $name;

    #[Column]
    public ?string $description;

    #[Column]
    #[Index]
    public ?int $stock;
}
