<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'comments';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Comment';

    public function tableUp(Blueprint $table): void
    {
        $table->id()->primary();
        $table->unsignedBigInteger('commentable_id');
        $table->string('commentable_type');
        $table->timestamps();

        $table->foreign('commentable_id', 'comments_category_id_foreign')->on('categories')->references('id');
        $table->foreign('commentable_id', 'comments_variant_id_foreign')->on('variants')->references('id');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
