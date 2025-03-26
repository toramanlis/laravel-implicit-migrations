<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use <<sourceClass>> as Source;

return new class extends Migration
{
    public const TABLE_NAME = '<<tableNameNew>>';

    public function getSource(): string
    {
        return <<source>>;
    }

    public function tableUp(Blueprint $table): void
    {
        <<up>>
    }

    public function tableDown(Blueprint $table): void
    {
        <<down>>
    }

    public function up(): void
    {
        Schema::table(<<tableNameOld>>, function (Blueprint $table) {
            $this->tableUp($table);
        });
    }

    public function down(): void
    {
        Schema::table(static::TABLE_NAME, function (Blueprint $table) {
            $this->tableDown($table);
        });
    }
};
