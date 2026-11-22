<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Linkul public este acum pe congregatie si acopera toate zilele ei.
     */
    public function up(): void
    {
        Schema::table('public_congregation_week_links', function (Blueprint $table): void {
            $table->foreignId('week_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('public_congregation_week_links', function (Blueprint $table): void {
            $table->foreignId('week_id')->nullable(false)->change();
        });
    }
};
