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
        $table->unsignedBigInteger('role_id');
        $table->timestamps();

        $table->foreign('role_id', 'users_role_id_foreign')->on('roles')->references('id');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
