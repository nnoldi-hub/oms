<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_meals', function (Blueprint $table) {
            $table->foreignId('soup_menu_id')->nullable()->after('menu_id')->constrained('menus')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_meals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('soup_menu_id');
        });
    }
};
