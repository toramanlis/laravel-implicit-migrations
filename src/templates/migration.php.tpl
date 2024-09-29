<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    <<tableNames>>

    protected const MODE = '<<migrationMode>>';

    protected const SOURCE = '<<source>>';

    public function tableUp(Blueprint $table): void
    {
        <<up>>
    }

    public function tableDown(Blueprint $table): void
    {
        <<down>>
    }
};
