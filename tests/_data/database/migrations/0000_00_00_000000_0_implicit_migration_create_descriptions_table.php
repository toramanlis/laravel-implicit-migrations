<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Description as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'descriptions';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->id();
        $table->unsignedBigInteger('describeable_id');
        $table->string('describeable_type');
        $table->timestamps();

        $table->foreign('describeable_id', 'descriptions_category_id_foreign')->on('categories')->references('id');
        $table->foreign('describeable_id', 'descriptions_variant_id_foreign')->on('variants')->references('id');
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
