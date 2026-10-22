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
            $table->decimal('package_quantity', 10, 3)->nullable()->after('unit_price');
            $table->boolean('has_no_allergens')->default(false)->after('package_quantity');
        });

        // Ingredient generic pentru condimentare si ulei de gatit: se adauga o singura
        // data in fiecare reteta si se contabilizeaza cu un pret fix pe portie.
        DB::table('ingredients')->updateOrInsert(
            ['name' => 'Diverse (sare, ulei de gatit, condimente)', 'unit' => 'buc'],
            [
                'unit_price' => 0.50,
                'package_quantity' => null,
                'has_no_allergens' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table): void {
            $table->dropColumn(['package_quantity', 'has_no_allergens']);
        });
    }
};
