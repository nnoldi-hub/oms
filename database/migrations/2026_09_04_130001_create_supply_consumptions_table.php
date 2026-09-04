<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_supply_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supply_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('estimated_quantity', 10, 3)->default(0);
            $table->decimal('actual_quantity', 10, 3)->default(0);
            $table->decimal('waste_quantity', 10, 3)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['daily_supply_plan_id', 'supply_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_consumptions');
    }
};
