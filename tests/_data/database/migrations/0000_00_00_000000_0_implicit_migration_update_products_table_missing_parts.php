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
        $table->dropColumn('sku');
        $table->renameColumn('title', 'category');
        $table->string('category')->change();
        $table->string('name');
        $table->integer('stock')->nullable();

        $table->renameIndex('products_quantity_index', 'products_stock_index');
        $table->index('name', 'products_name_index');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->dropColumn('name');
        $table->dropColumn('stock');
        $table->renameColumn('category', 'title');
        $table->addColumn('bigInteger', 'category', ['change' => true])->unsigned()->change();
        $table->addColumn('string', 'sku', ['unique' => true]);

        $table->renameIndex('products_stock_index', 'products_quantity_index');
    }
};
