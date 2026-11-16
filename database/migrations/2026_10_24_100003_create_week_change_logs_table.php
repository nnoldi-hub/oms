<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('week_change_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->default('move');
            $table->foreignId('week_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('second_week_id')->nullable()->constrained('weeks')->nullOnDelete();
            $table->foreignId('from_congregation_id')->nullable()->constrained('congregations')->nullOnDelete();
            $table->foreignId('to_congregation_id')->nullable()->constrained('congregations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('days_moved')->default(0);
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('week_change_logs');
    }
};
