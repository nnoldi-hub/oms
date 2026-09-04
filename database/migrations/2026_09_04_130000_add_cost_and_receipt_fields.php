<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_items', function (Blueprint $table): void {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('unit');
        });

        Schema::table('supply_contributions', function (Blueprint $table): void {
            $table->decimal('delivered_quantity', 10, 3)->nullable()->after('quantity');
            $table->dateTime('delivered_at')->nullable()->after('delivery_status');
            $table->string('received_by')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('supply_contributions', function (Blueprint $table): void {
            $table->dropColumn(['delivered_quantity', 'delivered_at', 'received_by']);
        });

        Schema::table('supply_items', function (Blueprint $table): void {
            $table->dropColumn('unit_cost');
        });
    }
};
