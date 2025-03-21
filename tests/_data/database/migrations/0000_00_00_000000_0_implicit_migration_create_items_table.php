<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'items';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Item';

    public function tableUp(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();

        $table->primary('id', 'order_items_id_primary');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
