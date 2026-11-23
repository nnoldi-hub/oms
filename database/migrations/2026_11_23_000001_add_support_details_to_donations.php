<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table): void {
            $table->foreignId('ingredient_id')->nullable()->after('food_type')->constrained('ingredients')->nullOnDelete();
            $table->string('storage_method')->nullable()->after('unit');
            $table->dateTime('delivery_until')->nullable()->after('storage_method');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('ingredient_id');
            $table->dropColumn(['storage_method', 'delivery_until']);
        });
    }
};
