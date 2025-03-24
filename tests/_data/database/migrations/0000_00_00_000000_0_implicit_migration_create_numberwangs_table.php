<?php

use Toramanlis\ImplicitMigrations\Database\Migrations\ImplicitMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends ImplicitMigration
{
    protected const TABLE_NAME = 'numberwangs';

    protected const MODE = 'create';

    protected const SOURCE = 'Toramanlis\Tests\Data\Models\Numberwang';

    public function tableUp(Blueprint $table): void
    {
        $table->id()->primary();
        $table->tinyIncrements('tiny');
        $table->smallIncrements('small');
        $table->mediumIncrements('medium');
        $table->increments('regular');
        $table->addColumn('decimal', 'other', ['total' => 8, 'places' => 2])
            ->comment('That\'s numberang!!!!!!!!')
            ->default(123456789000.11111)
            ->nullable()
            ->unsigned();
        $table->rememberToken();
        $table->softDeletesTz('deleted_at');
        $table->string('none');
        $table->timestamps();

        $table->charset('utf8');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->drop();
    }
};
