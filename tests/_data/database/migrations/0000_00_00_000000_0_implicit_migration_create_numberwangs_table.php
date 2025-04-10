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
        $table->id('big_increments');
        $table->tinyInteger('tiny');
        $table->smallInteger('small');
        $table->mediumInteger('medium');
        $table->integer('regular');
        $table->bigInteger('big');
        $table->unsignedTinyInteger('unsigned_tiny');
        $table->unsignedSmallInteger('unsigned_small');
        $table->unsignedMediumInteger('unsigned_medium');
        $table->unsignedInteger('unsigned_regular');
        $table->unsignedBigInteger('unsigned_big');
        $table->tinyIncrements('tiny_increments');
        $table->smallIncrements('small_increments');
        $table->mediumIncrements('medium_increments');
        $table->increments('regular_increments');
        $table->increments('normal');
        $table->decimal('other')
            ->comment('That\'s numberang!!!!!!!!')
            ->default(123456789000.11111)
            ->nullable()
            ->unsigned();
        $table->string('remember_token', 100);
        $table->softDeletesTz('deleted_at');
        $table->string('none');
        $table->binary('binary');
        $table->float('float');
        $table->computed('computed', '5');
        $table->string('string', 12);
        $table->dateTime('date_time');
        $table->dateTimeTz('date_time_tz');
        $table->decimal('decimal', 4);
        $table->enum('enum', ['a', 'b']);
        $table->geography('geography', null, 4325);
        $table->geometry('geometry');
        $table->set('set', ['a', 'b']);
        $table->time('time');
        $table->timestamp('timestamp');
        $table->timeTz('time_tz');
        $table->timestampTz('timestamp_tz');
        $table->timestamps();

        $table->charset('utf8');
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
