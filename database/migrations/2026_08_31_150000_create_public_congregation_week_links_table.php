<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_congregation_week_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained()->cascadeOnDelete();
            $table->foreignId('congregation_id')->constrained()->cascadeOnDelete();
            $table->uuid('token')->unique();
            $table->timestamps();

            $table->unique(['week_id', 'congregation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_congregation_week_links');
    }
};