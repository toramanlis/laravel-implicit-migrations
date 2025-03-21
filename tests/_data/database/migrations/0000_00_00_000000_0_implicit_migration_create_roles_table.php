<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'roles';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Role';

    public function tableUp(Blueprint $table): void
    {
        $table->id();
        $table->timestamps();
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
