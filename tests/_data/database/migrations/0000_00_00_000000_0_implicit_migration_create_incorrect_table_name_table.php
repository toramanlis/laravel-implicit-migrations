<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Permission as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'incorrect_table_name';

    public function getSource(): string
    {
        return Source::class . '::roles';
    }

    public function tableUp(Blueprint $table): void
    {
        $table->foreignId('permission_id')->constrained('permissions');
        $table->foreignId('role_id')->constrained('roles');
        $table->boolean('is_temporary');

        $table->charset('idk');
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
