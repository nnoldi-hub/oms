<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cererile de schimbare se fac pe zile: inlocuirea unei congregatii la o
     * data sau interschimbarea a doua congregatii intre doua zile.
     */
    public function up(): void
    {
        Schema::table('week_change_requests', function (Blueprint $table): void {
            $table->foreignId('week_id')->nullable()->change();
            $table->foreignId('daily_meal_id')->nullable()->after('week_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_daily_meal_id')->nullable()->after('daily_meal_id')->constrained('daily_meals')->cascadeOnDelete();
            $table->foreignId('from_congregation_id')->nullable()->after('preferred_congregation_id')->constrained('congregations')->nullOnDelete();
            $table->string('change_type')->nullable()->after('reason')->index();
        });
    }

    public function down(): void
    {
        Schema::table('week_change_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('daily_meal_id');
            $table->dropConstrainedForeignId('target_daily_meal_id');
            $table->dropConstrainedForeignId('from_congregation_id');
            $table->dropColumn('change_type');
            $table->foreignId('week_id')->nullable(false)->change();
        });
    }
};
