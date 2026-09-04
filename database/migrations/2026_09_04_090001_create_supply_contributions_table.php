<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('congregation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supply_item_id')->constrained()->cascadeOnDelete();
            $table->date('delivery_date')->index();
            $table->decimal('quantity', 10, 3);
            $table->string('responsible_name')->nullable();
            $table->enum('delivery_status', ['confirmed', 'in_transit', 'delivered'])->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_contributions');
    }
};
