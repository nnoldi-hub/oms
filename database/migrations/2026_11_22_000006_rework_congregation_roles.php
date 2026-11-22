<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregations', function (Blueprint $table): void {
            $table->string('type')->default('contribuie')->index();
            $table->text('support_notes')->nullable();
        });

        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->text('contribution_notes')->nullable();
        });

        // Backfill-ul vechi a pus in pivot si congregatia care gatea (congregation_id).
        // Ea nu contribuie, ci pregateste masa: o scoatem din lista de contribuitoare.
        $rows = DB::table('congregation_daily_meal as cdm')
            ->join('daily_meals as dm', 'dm.id', '=', 'cdm.daily_meal_id')
            ->whereColumn('cdm.congregation_id', 'dm.congregation_id')
            ->select('cdm.daily_meal_id', 'cdm.congregation_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('congregation_daily_meal')
                ->where('daily_meal_id', $row->daily_meal_id)
                ->where('congregation_id', $row->congregation_id)
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::table('daily_meals', function (Blueprint $table): void {
            $table->dropColumn('contribution_notes');
        });

        Schema::table('congregations', function (Blueprint $table): void {
            $table->dropColumn(['support_notes', 'type']);
        });
    }
};
