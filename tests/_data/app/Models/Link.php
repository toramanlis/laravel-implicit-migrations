<?php

namespace Toramanlis\Tests\Data\Models;

use Illuminate\Database\Eloquent\Model;
use Toramanlis\ImplicitMigrations\Attributes\Column;
use Toramanlis\ImplicitMigrations\Attributes\ForeignKey;
use Toramanlis\ImplicitMigrations\Attributes\Primary;
use Toramanlis\ImplicitMigrations\Attributes\Table;
use Toramanlis\Tests\Data\Models\Redirection;

#[Table]
#[Primary(column: ['id'])]
#[ForeignKey(on: 'affiliates', references: 'id')]
#[ForeignKey(on: 'promotions', references: 'id', column: ['promotion_id', 'affiliate_id'])]
#[ForeignKey(on: 'campaigns', references: ['code', 'id'], column: ['campaign_id'])]
class Link extends Model
{
    public $timestamps = false;

    #[Column]
    public int $id;

    #[ForeignKey(on: Redirection::class, references: 'to')]
    public string $url;

    #[ForeignKey(on: 'redirections', references: 'from')]
    public string $old_url;

    #[ForeignKey(on: 'contracts', references: 'code')]
    public string $contract_code;
}
