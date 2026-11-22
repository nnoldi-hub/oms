<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fiecare zi de masa are trei congregatii. Coloana veche de pe daily_meals
     * ramane doar pentru compatibilitate si devine prima congregatie din pivot.
     */
    public function up(): void
    {
        Schema::create('congregation_daily_meal', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_meal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('congregation_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['daily_meal_id', 'congregation_id']);
        });

        DB::table('daily_meals')
            ->whereNotNull('congregation_id')
            ->orderBy('id')
            ->each(function (object $meal): void {
                DB::table('congregation_daily_meal')->insertOrIgnore([
                    'daily_meal_id' => $meal->id,
                    'congregation_id' => $meal->congregation_id,
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('congregation_daily_meal');
    }
};
