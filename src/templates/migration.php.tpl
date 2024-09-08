<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Doctrine\DBAL\Schema\Table;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = '<<tableName>>';

    protected static $mode = '<<migrationMode>>';

    public function tableUp(Table $table): void
    {
        <<up>>
    }

    public function tableDown(Table $table): void
    {
        <<down>>
    }
};
