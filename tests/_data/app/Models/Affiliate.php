<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\ForeignKey;
use Toramanlis\ImplicitMigrations\Attributes\Unique;

class Affiliate extends Model
{
    #[Column]
    #[ForeignKey(on: 'users', references: 'id', column: ['user_id'])]
    public int $user_id;

    #[ForeignKey(on: Store::class)]
    public $store_id;

    #[Unique(name: 'affiliate_code_no_duplicate')]
    #[Column(type: 'string', nullable: true, default: '')]
    public $code;
}
