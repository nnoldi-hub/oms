<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_change_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_meal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('week_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('congregation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('meal_date')->nullable()->index();
            $table->string('event_type');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_change_logs');
    }
};
