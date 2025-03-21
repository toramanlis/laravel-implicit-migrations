<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'coupons';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Coupon';

    public function tableUp(Blueprint $table): void
    {
        $table->id();
        $table->string('builder')->default('Illuminate\\Database\\Eloquent\\Builder');
        $table->string('collection_class')->default('Illuminate\\Database\\Eloquent\\Collection');
        $table->unsignedBigInteger('promotion_id');
        $table->timestamps();

        $table->foreign('promotion_id', 'coupons_promotion_id_foreign')->on('promotions')->references('id');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
