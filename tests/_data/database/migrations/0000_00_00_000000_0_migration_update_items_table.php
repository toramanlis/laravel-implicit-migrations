<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @off  */
    public function getSource(): string
    {
        return 'friend of a friend';
    }

    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('cart_hash');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('cart_hash');
        });
    }
};
