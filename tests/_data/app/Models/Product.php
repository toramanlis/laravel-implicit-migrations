<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\Index;
use Toramanlis\ImplicitMigrations\Attributes\Table;

/**
 * @package Toramanlis\Tests\Data\Models
 *
 * @index(['name'], name: 'products_name_index')
 */
#[Table(charset: 'utf8mb4', collation: 'utf8mb4_unicode_ci')]
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

    #[Column]
    public string $manufacturer;

    public ?string $description;

    #[Column]
    #[Index]
    public ?int $stock;
}
