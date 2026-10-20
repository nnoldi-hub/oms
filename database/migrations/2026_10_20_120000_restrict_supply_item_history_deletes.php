<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['supply_contributions', 'supply_consumptions'];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['supply_item_id']);
                $table->foreign('supply_item_id')
                    ->references('id')->on('supply_items')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        foreach (self::TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['supply_item_id']);
                $table->foreign('supply_item_id')
                    ->references('id')->on('supply_items')
                    ->cascadeOnDelete();
            });
        }
    }
};
