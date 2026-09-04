<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', ['snack', 'water', 'meal', 'auxiliary']);
            $table->string('unit', 20);
            $table->decimal('current_stock', 10, 3)->default(0);
            $table->decimal('minimum_stock', 10, 3)->default(0);
            $table->decimal('estimated_daily_consumption', 10, 3)->default(0);
            $table->decimal('actual_consumption', 10, 3)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_items');
    }
};
