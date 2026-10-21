<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->unsignedInteger('confirmed_people')->nullable()->after('estimated_people');
        });
    }

    public function down(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->dropColumn('confirmed_people');
        });
    }
};
