<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Reaction as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'reactables';

    public function getSource(): string
    {
        return Source::class . '::variations';
    }

    public function tableUp(Blueprint $table): void
    {
        $table->foreignId('reaction_id')->constrained('reactions');
        $table->unsignedBigInteger('reactable_id');
        $table->string('reactable_type');
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
