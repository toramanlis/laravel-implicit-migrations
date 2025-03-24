<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'descriptions';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Description';

    public function tableUp(Blueprint $table): void
    {
        $table->id()->primary();
        $table->unsignedBigInteger('describeable_id');
        $table->string('describeable_type');
        $table->timestamps();

        $table->foreign('describeable_id', 'descriptions_category_id_foreign')->on('categories')->references('id');
        $table->foreign('describeable_id', 'descriptions_variant_id_foreign')->on('variants')->references('id');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
