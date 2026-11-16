<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table): void {
            $table->foreignId('created_by_congregation_id')
                ->nullable()
                ->after('cost_per_portion')
                ->constrained('congregations')
                ->nullOnDelete();
            $table->boolean('is_reviewed')->default(true)->after('created_by_congregation_id');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_congregation_id');
            $table->dropColumn('is_reviewed');
        });
    }
};
