<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\ForeignKey;
use Toramanlis\ImplicitMigrations\Attributes\Char;
use Toramanlis\ImplicitMigrations\Attributes\Primary;
use Toramanlis\ImplicitMigrations\Attributes\Unique;

/**
 * @someOtherAnnotationToIgnore()
*/
#[Primary(column: ['id'])]
#[Column(type: 'timestamp', name: 'created_at')]
#[Column(type: 'timestamp', name: 'updated_at')]
#[Column(type: 'timestamp', name: 'deleted_at')]
class Affiliate extends Model
{
    use SoftDeletes;

    public $incrementing = false;

    #[Column(length: 10)]
    #[ForeignKey(on: 'users', references: 'id', column: ['user_id'])]
    public int $user_id;

    #[ForeignKey(on: Store::class)]
    public $store_id;

    #[Unique(name: 'affiliate_code_no_duplicate')]
    #[Column(type: 'string', nullable: true, default: '')]
    public $code;

    #[Char]
    public $tier;
}
