<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->boolean('fixed_cost_per_portion')->default(false)->after('has_no_allergens');
        });

        DB::table('ingredients')
            ->where('name', 'Diverse (sare, ulei de gatit, condimente)')
            ->where('unit', 'buc')
            ->update(['fixed_cost_per_portion' => true]);
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->dropColumn('fixed_cost_per_portion');
        });
    }
};
