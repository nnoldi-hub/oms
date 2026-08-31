<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->foreignId('dessert_menu_id')->nullable()->after('soup_menu_id')->constrained('menus')->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE menus MODIFY type ENUM('main', 'soup', 'dessert') NOT NULL DEFAULT 'main'");
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('menus', function (Blueprint $table): void {
                $table->string('type')->default('main')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE menus MODIFY type ENUM('main', 'soup') NOT NULL DEFAULT 'main'");
        }

        if (DB::getDriverName() === 'sqlite') {
            Schema::table('menus', function (Blueprint $table): void {
                $table->string('type')->default('main')->change();
            });
        }

        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('dessert_menu_id');
        });
    }
};