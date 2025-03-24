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
        $table->renameColumn('name', 'title');
        $table->unsignedBigInteger('category')->change();
        $table->string('sku')->unique();

        $table->dropColumn('manufacturer');

        $table->renameIndex('products_stock_index', 'products_quantity_index');
        $table->dropIndex('products_name_index');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->renameColumn('title', 'name');
        $table->dropColumn('sku');

        $table->renameIndex('products_quantity_index', 'products_stock_index');
        $table->index('name', 'products_name_index');
    }
};
