<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Product as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'products';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->id('p_id');
        $table->foreignId('manufacturer')->constrained('manufacturers');
        $table->string('name')->index();
        $table->string('category');
        $table->string('brand')->default('TorCorp')->nullable();
        $table->integer('stock')->index()->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->charset('utf8mb4');
        $table->collation('utf8mb4_unicode_ci');
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
