<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'users';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\User';

    public function tableUp(Blueprint $table): void
    {
        $table->id()->primary();
        $table->unsignedBigInteger('profile_id');
        $table->timestamps();

        $table->foreign('profile_id', 'users_profile_id_foreign')->on('profiles')->references('id');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
