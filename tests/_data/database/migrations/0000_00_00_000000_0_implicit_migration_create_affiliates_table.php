<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'affiliates';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Affiliate';

    public function tableUp(Blueprint $table): void
    {
        $table->id();
        $table->id('store_id');
        $table->integer('user_id');
        $table->string('code')->nullable();
        $table->timestamps();

        $table->foreign('user_id', 'affiliates_user_id_foreign')->on('users')->references('id');
        $table->foreign('store_id', 'affiliates_store_id_foreign')->on('stores')->references('id');
        $table->unique('code', 'affiliates_code_unique');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
