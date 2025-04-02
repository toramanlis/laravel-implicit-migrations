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
        $table->rememberToken()->change();

        $table->dropIndex('numberwangs_none_index');

        $table->charset('utf8');
    }

    public function tableDown(Blueprint $table): void
    {
        $table->rememberToken()->nullable()->change();

        $table->index('none', null, 'some');

        $table->charset('utf16');
    }

    public function up(): void
    {
        Schema::table(static::TABLE_NAME, function (Blueprint $table) {
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
