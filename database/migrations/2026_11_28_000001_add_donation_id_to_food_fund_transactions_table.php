<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Donațiile în bani și cheltuielile făcute din ele trebuie să apară în același
     * registru financiar, cu legătura explicată între ele.
     */
    public function up(): void
    {
        Schema::table('food_fund_transactions', function (Blueprint $table): void {
            $table->foreignId('donation_id')
                ->nullable()
                ->after('daily_meal_id')
                ->constrained('donations')
                ->nullOnDelete();
        });

        $this->linkExistingMoneyDonations();
    }

    public function down(): void
    {
        Schema::table('food_fund_transactions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('donation_id');
        });
    }

    /**
     * Pentru donațiile deja înregistrate, refolosim o încasare manuală compatibilă
     * (aceeași congregație, dată și sumă) sau cream una, fără să dublăm soldul.
     */
    private function linkExistingMoneyDonations(): void
    {
        DB::table('donations')
            ->where('kind', 'bani')
            ->whereIn('status', ['primita', 'folosita'])
            ->whereNotNull('congregation_id')
            ->where('amount_ron', '>', 0)
            ->orderBy('id')
            ->get()
            ->each(function (object $donation): void {
                $date = substr((string) ($donation->date ?: $donation->donation_date), 0, 10);

                if ($date === '') {
                    return;
                }

                $candidate = DB::table('food_fund_transactions')
                    ->whereNull('donation_id')
                    ->where('type', 'income')
                    ->where('category', 'donation')
                    ->where('congregation_id', $donation->congregation_id)
                    ->whereDate('transaction_date', $date)
                    ->where('amount', $donation->amount_ron)
                    ->first();

                if ($candidate !== null) {
                    DB::table('food_fund_transactions')
                        ->where('id', $candidate->id)
                        ->update(['donation_id' => $donation->id]);

                    return;
                }

                $description = 'Donație în bani primită';

                if (filled($donation->donor_name)) {
                    $description .= ' de la '.$donation->donor_name;
                }

                if (filled($donation->description)) {
                    $description .= ' · '.$donation->description;
                }

                if (filled($donation->notes)) {
                    $description .= ' · '.$donation->notes;
                }

                DB::table('food_fund_transactions')->insert([
                    'type' => 'income',
                    'transaction_date' => $date,
                    'amount' => $donation->amount_ron,
                    'category' => 'donation',
                    'counterparty' => $donation->donor_name,
                    'description' => $description,
                    'recorded_by' => $donation->recorded_by,
                    'congregation_id' => $donation->congregation_id,
                    'donation_id' => $donation->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
};
