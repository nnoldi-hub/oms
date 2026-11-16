<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('week_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained()->cascadeOnDelete();
            $table->foreignId('congregation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->foreignId('preferred_week_id')->nullable()->constrained('weeks')->nullOnDelete();
            $table->foreignId('preferred_congregation_id')->nullable()->constrained('congregations')->nullOnDelete();
            $table->string('status')->default('pending')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('week_change_requests');
    }
};
