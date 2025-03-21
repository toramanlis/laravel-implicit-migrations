<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\Index;
use Toramanlis\ImplicitMigrations\Attributes\Table;

#[Table(charset: 'utf8mb4', collation: 'utf8mb4_unicode_ci')]
#[Index(column: ['name'], name: 'products_name_index')]
class Product extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'p_id';

    #[Column]
    public string $name;

    #[Column]
    public string $category;

    #[Column('string', true, 'TorCorp')]
    public string $brand = 'TorCorp';

    public ?string $description;

    #[Column]
    #[Index]
    public ?int $stock;
}
