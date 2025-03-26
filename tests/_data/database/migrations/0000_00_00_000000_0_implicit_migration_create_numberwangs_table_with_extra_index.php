<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Numberwang as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'numberwangs';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->id();
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

        $table->index('none', 'numberwangs_none_index', 'some');

        $table->charset('utf16');
    }

    public function up(): void
    {
        Schema::create(static::TABLE_NAME, function (Blueprint $table) {
            $this->tableUp($table);
        });
    }

    public function down(): void
    {
        Schema::drop(static::TABLE_NAME);
    }
};
