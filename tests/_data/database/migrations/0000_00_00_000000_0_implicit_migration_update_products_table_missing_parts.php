<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'products';

    protected const MODE = 'update';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Product';

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
};
