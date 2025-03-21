<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'number_wangs';

    protected const MODE = 'update';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\NumberWang';

    public function tableUp(Blueprint $table): void
    {
        $table->rememberToken()->change();

        $table->dropIndex('number_wangs_none_index');

        $table->charset('utf8');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->rememberToken()->nullable()->change();

        $table->index('none', 'number_wangs_none_index', 'some');

        $table->charset('utf16');
    }
};
