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
        $table->string('old_id');
        $table->string('new_id');
        $table->string('product_id');
        $table->timestamps();

        $table->primary(['old_id', 'new_id'], 'descriptions_old_id_new_id_primary');
        $table->index(['old_id', 'product_id'], 'descriptions_old_id_product_id_index');
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
