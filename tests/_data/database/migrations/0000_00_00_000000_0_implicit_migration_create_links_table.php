<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Link as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'links';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->integer('id')->primary();
        $table->unsignedBigInteger('affiliate_id');
        $table->unsignedBigInteger('promotion_id');
        $table->unsignedBigInteger('campaign_id');
        $table->string('url');
        $table->string('old_url');
        $table->unsignedBigInteger('contract_code');

        $table->foreign('affiliate_id', 'links_affiliate_id_foreign')->on('affiliates')->references('id');
        $table->foreign(['promotion_id', 'affiliate_id'], 'links_promotion_id_affiliate_id_foreign')->on('promotions')->references('id');
        $table->foreign('campaign_id', 'links_campaign_id_foreign')->on('campaigns')->references(['code', 'id']);
        $table->foreign('url', 'links_url_foreign')->on('redirections')->references('to');
        $table->foreign('old_url', 'links_old_url_foreign')->on('redirections')->references('from');
        $table->foreign('contract_code', 'links_contract_code_foreign')->on('contracts')->references('code');
    }

    public function up(): void
    {
        Schema::create(static::TABLE_NAME, function (Blueprint $table) {
            $this->tableUp($table);
        });
    }

    public function down(): void
    {
        Schema::drop(static::TABLE_NAME);
    }
};
