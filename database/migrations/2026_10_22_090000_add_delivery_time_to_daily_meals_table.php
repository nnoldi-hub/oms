<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->string('delivery_time')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->dropColumn('delivery_time');
        });
    }
};
