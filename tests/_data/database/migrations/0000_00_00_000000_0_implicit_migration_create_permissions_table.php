<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'permissions';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Permission';

    public function tableUp(Blueprint $table): void
    {
        $table->id()->primary();
        $table->timestamps();
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
