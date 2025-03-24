<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'refunds';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlist\Tests\Data\Models\Refund';

    public function tableUp(Blueprint $table): void
    {
        $table->id()->primary();
        $table->string('order_id')->index();
        $table->timestamps();
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
