<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Item as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'order_items';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->rename('items', 'order_items');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->rename('order_items', 'items');
    }

    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
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
