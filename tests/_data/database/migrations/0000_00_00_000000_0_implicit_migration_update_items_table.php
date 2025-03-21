<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME_OLD = 'items';
    protected const TABLE_NAME_NEW = 'order_items';

    protected const MODE = 'update';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Item';

    public function tableUp(Blueprint $table): void
    {
        $table->rename('items', 'order_items');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->rename('order_items', 'items');
    }
};
