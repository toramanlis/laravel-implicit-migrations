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
        $table->foreignId('contract_code')->constrained('contracts', 'code');
        $table->foreignId('campaign_id')->constrained('campaigns', ['code', 'id']);
        $table->foreignId('affiliate_id')->constrained('affiliates');
        $table->unsignedBigInteger('promotion_id');
        $table->string('url');
        $table->string('old_url');

        $table->foreign(['promotion_id', 'affiliate_id'])
            ->on('promotions')->references('id');
        $table->foreign('url')->on('redirections')->references('to');
        $table->foreign('old_url')->on('redirections')->references('from');
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
