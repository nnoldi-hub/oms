<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table): void {
            $table->decimal('cost_per_portion', 10, 2)->nullable()->after('packaging_cost');
        });

        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->decimal('actual_cost', 10, 2)->nullable()->after('maximum_budget');
            $table->boolean('is_cooked')->default(false)->after('is_free_day');
            $table->foreignId('actual_menu_id')->nullable()->after('menu_id')->constrained('menus')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('actual_menu_id');
            $table->dropColumn(['actual_cost', 'is_cooked']);
        });

        Schema::table('menus', function (Blueprint $table): void {
            $table->dropColumn('cost_per_portion');
        });
    }
};
