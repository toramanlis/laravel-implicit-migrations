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
        $table->dropColumn('title');
        $table->dropColumn('sku');
        $table->string('category')->change();
        $table->string('name')->index();
        $table->string('manufacturer');

        $table->renameIndex('products_quantity_index', 'products_stock_index');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->dropColumn('name');
        $table->dropColumn('manufacturer');
        $table->unsignedBigInteger('category')->change();
        $table->string('title');
        $table->string('sku')->unique();

        $table->renameIndex('products_stock_index', 'products_quantity_index');
    }

    public function up(): void
    {
        Schema::table(static::TABLE_NAME, function (Blueprint $table) {
            $this->tableUp($table);
        });
    }

    public function down(): void
    {
        Schema::table(static::TABLE_NAME, function (Blueprint $table) {
            $this->tableDown($table);
        });
    }
};
