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
        $table->unsignedBigInteger('role_id');
        $table->unsignedBigInteger('permission_id');
        $table->boolean('is_temporary');

        $table->foreign('role_id', 'incorrect_table_name_role_id_foreign')->on('roles')->references('id');
        $table->foreign('permission_id', 'incorrect_table_name_permission_id_foreign')->on('permissions')->references('id');

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
