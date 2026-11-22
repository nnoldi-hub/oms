<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->foreignId('updated_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
        });

        Schema::table('weeks', function (Blueprint $table): void {
            $table->foreignId('congregation_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        $fallback = DB::table('congregations')->orderBy('id')->value('id');

        if ($fallback !== null) {
            DB::table('weeks')->whereNull('congregation_id')->update(['congregation_id' => $fallback]);
        }

        Schema::table('weeks', function (Blueprint $table): void {
            $table->foreignId('congregation_id')->nullable(false)->change();
        });

        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('updated_by');
        });
    }
};
