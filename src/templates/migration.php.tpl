<?php

use Toramanlis\ImplicitMigrations\Migration\ImplicitMigration;
use Doctrine\DBAL\Schema\Table;
use Toramanlis\ImplicitMigrations\Schemas\DroppableTable;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = '<<tableName>>';

    public function tableUp(Table $table): void
    {
        <<up>>
    }

    public function tableDown(DroppableTable $table): void
    {
        <<down>>
    }
};
