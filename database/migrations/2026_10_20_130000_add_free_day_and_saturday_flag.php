<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_meals', function (Blueprint $table) {
            $table->boolean('is_free_day')->default(false)->after('status');
        });

        Schema::table('weeks', function (Blueprint $table) {
            $table->boolean('include_saturday')->default(false)->after('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('daily_meals', function (Blueprint $table) {
            $table->dropColumn('is_free_day');
        });

        Schema::table('weeks', function (Blueprint $table) {
            $table->dropColumn('include_saturday');
        });
    }
};
