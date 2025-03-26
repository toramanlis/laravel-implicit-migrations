<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Store as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'stores';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->timestamps();

        $table->foreign('user_id', 'stores_user_id_foreign')->on('users')->references('id');
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
