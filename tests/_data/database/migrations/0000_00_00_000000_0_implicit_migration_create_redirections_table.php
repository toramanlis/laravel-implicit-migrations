<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'redirections';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlist\Tests\Data\Models\Redirection';

    public function tableUp(Blueprint $table): void
    {
        $table->id()->primary();
        $table->string('to');
        $table->string('from');
        $table->timestamps();
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
