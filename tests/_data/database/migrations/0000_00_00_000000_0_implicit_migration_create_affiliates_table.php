<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Affiliate as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'affiliates';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->timestamp('created_at');
        $table->timestamp('updated_at');
        $table->timestamp('deleted_at');
        $table->integer('user_id');
        $table->string('code')->nullable()->unique('affiliate_code_no_duplicate');
        $table->unsignedBigInteger('id')->primary();
        $table->unsignedBigInteger('store_id');

        $table->foreign('user_id', 'affiliates_user_id_foreign')->on('users')->references('id');
        $table->foreign('store_id', 'affiliates_store_id_foreign')->on('stores')->references('id');
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
