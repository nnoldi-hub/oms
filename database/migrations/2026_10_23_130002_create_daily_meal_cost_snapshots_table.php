<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_meal_cost_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_meal_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('congregation_id')->nullable()->constrained()->nullOnDelete();
            $table->date('meal_date')->nullable()->index();
            $table->unsignedInteger('portions')->default(0);
            $table->decimal('ingredients_cost', 10, 2)->default(0);
            $table->decimal('fixed_costs_cost', 10, 2)->default(0);
            $table->decimal('packaging_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->decimal('cost_per_portion', 10, 2)->nullable();
            $table->boolean('has_missing_prices')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_meal_cost_snapshots');
    }
};
