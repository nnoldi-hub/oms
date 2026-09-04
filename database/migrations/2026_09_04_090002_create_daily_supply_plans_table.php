<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_supply_plans', function (Blueprint $table) {
            $table->id();
            $table->date('plan_date')->unique();
            $table->foreignId('daily_meal_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('people_count')->default(0);
            $table->decimal('still_water_required', 10, 3)->default(0);
            $table->decimal('mineral_water_required', 10, 3)->default(0);
            $table->decimal('snacks_required', 10, 3)->default(0);
            $table->decimal('desserts_required', 10, 3)->default(0);
            $table->decimal('still_water_confirmed', 10, 3)->default(0);
            $table->decimal('mineral_water_confirmed', 10, 3)->default(0);
            $table->decimal('snacks_confirmed', 10, 3)->default(0);
            $table->decimal('desserts_confirmed', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_supply_plans');
    }
};
