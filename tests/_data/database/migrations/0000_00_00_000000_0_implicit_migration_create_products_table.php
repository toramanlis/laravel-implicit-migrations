<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'products';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Product';

    public function tableUp(Blueprint $table): void
    {
        $table->id('p_id')->primary();
        $table->string('name')->index();
        $table->string('category');
        $table->string('brand')->default('TorCorp')->nullable();
        $table->string('manufacturer');
        $table->integer('stock')->index()->nullable();
        $table->timestamps();
        $table->softDeletes();

        $table->charset('utf8mb4');
        $table->collation('utf8mb4_unicode_ci');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
