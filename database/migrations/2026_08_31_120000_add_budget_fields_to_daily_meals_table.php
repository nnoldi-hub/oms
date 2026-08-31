<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->decimal('maximum_budget', 10, 2)->nullable()->after('estimated_people');
            $table->unsignedSmallInteger('contributor_count')->nullable()->after('maximum_budget');
        });
    }

    public function down(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->dropColumn(['maximum_budget', 'contributor_count']);
        });
    }
};