<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME_OLD = '<<tableNameOld>>';
    protected const TABLE_NAME_NEW = '<<tableNameNew>>';

    protected const SOURCE = '<<source>>';
    protected const MODE = '<<migrationMode>>';

    public function tableUp(Blueprint $table): void
    {
        <<up>>
    }

    public function tableDown(Blueprint $table): void
    {
        <<down>>
    }
};
