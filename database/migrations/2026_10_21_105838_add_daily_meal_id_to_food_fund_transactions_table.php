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
        Schema::table('food_fund_transactions', function (Blueprint $table) {
            $table->foreignId('daily_meal_id')->nullable()->after('recorded_by')->constrained()->nullOnDelete();
            $table->string('volunteer_contribution_status')->nullable()->after('daily_meal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_fund_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('daily_meal_id');
            $table->dropColumn('volunteer_contribution_status');
        });
    }
};
