<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fiecare operatiune din fondul alimentar apartine unei congregatii, ca fiecare
     * coordonator sa-si gestioneze propriile costuri de aprovizionare.
     */
    public function up(): void
    {
        Schema::table('food_fund_transactions', function (Blueprint $table) {
            $table->foreignId('congregation_id')->nullable()->after('daily_meal_id')->constrained()->nullOnDelete();
        });

        // Completam congregatia din ziua de masa legata sau, in lipsa ei, din masa programata in aceeasi data.
        $mealsById = DB::table('daily_meals')->pluck('congregation_id', 'id');
        $mealsByDate = DB::table('daily_meals')
            ->whereNotNull('congregation_id')
            ->get(['meal_date', 'congregation_id'])
            ->mapWithKeys(fn (object $meal): array => [substr((string) $meal->meal_date, 0, 10) => $meal->congregation_id]);

        DB::table('food_fund_transactions')->orderBy('id')->get(['id', 'daily_meal_id', 'transaction_date'])
            ->each(function (object $transaction) use ($mealsById, $mealsByDate): void {
                $congregationId = $transaction->daily_meal_id !== null
                    ? ($mealsById[$transaction->daily_meal_id] ?? null)
                    : ($mealsByDate[substr((string) $transaction->transaction_date, 0, 10)] ?? null);

                if ($congregationId !== null) {
                    DB::table('food_fund_transactions')->where('id', $transaction->id)->update(['congregation_id' => $congregationId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('food_fund_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('congregation_id');
        });
    }
};
