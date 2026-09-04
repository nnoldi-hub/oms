<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_meal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('member_count')->default(0);
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('location')->nullable();
            $table->decimal('water_required', 10, 3)->default(0);
            $table->decimal('snacks_required', 10, 3)->default(0);
            $table->string('supply_responsible')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_teams');
    }
};
