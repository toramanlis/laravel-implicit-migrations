<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'links';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Link';

    public function tableUp(Blueprint $table): void
    {
        $table->id()->primary();
        $table->id('affiliate_id');
        $table->unsignedBigInteger('promotion_id');
        $table->unsignedBigInteger('campaign_id');
        $table->string('url');
        $table->string('old_url');
        $table->unsignedBigInteger('contract_code');
        $table->timestamps();

        $table->foreign('affiliate_id', 'links_affiliate_id_foreign')->on('affiliates')->references('id');
        $table->foreign(['promotion_id', 'affiliate_id'], 'links_promotion_id_affiliate_id_foreign')->on('promotions')->references('id');
        $table->foreign('campaign_id', 'links_campaign_id_foreign')->on('campaigns')->references(['code', 'id']);
        $table->foreign('url', 'links_url_foreign')->on('redirections')->references('to');
        $table->foreign('old_url', 'links_old_url_foreign')->on('redirections')->references('from');
        $table->foreign('contract_code', 'links_contract_code_foreign')->on('contracts')->references('code');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
