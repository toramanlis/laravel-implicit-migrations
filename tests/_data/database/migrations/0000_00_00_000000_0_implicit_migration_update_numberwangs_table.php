<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'numberwangs';

    protected const MODE = 'update';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Numberwang';

    public function tableUp(Blueprint $table): void
    {
        $table->rememberToken()->change();

        $table->dropIndex('numberwangs_none_index');

        $table->charset('utf8');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->rememberToken()->nullable()->change();

        $table->index('none', 'numberwangs_none_index', 'some');

        $table->charset('utf16');
    }
};
