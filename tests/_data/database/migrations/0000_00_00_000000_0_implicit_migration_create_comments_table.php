<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Toramanlis\Tests\Data\Models\Comment as Source;

return new class extends Migration
{
    public const TABLE_NAME = 'comments';

    public function getSource(): string
    {
        return Source::class;
    }

    public function tableUp(Blueprint $table): void
    {
        $table->id();
        $table->unsignedBigInteger('commentable_id');
        $table->string('commentable_type');
        $table->timestamps();

        $table->foreign('commentable_id', 'comments_category_id_foreign')->on('categories')->references('id');
        $table->foreign('commentable_id', 'comments_variant_id_foreign')->on('variants')->references('id');
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
