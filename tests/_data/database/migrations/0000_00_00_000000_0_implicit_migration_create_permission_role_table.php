<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'permission_role';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Permission::roles';

    public function tableUp(Blueprint $table): void
    {
        $table->unsignedBigInteger('role_id');
        $table->unsignedBigInteger('permission_id');
        $table->boolean('is_temporary');

        $table->foreign('role_id', 'permission_role_role_id_foreign')->on('roles')->references('id');
        $table->foreign('permission_id', 'permission_role_permission_id_foreign')->on('permissions')->references('id');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
