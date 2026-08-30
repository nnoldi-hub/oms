<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('congregations', function (Blueprint $table) {
            $table->string('assistant_name')->nullable()->after('name');
            $table->string('assistant_phone', 30)->nullable()->after('assistant_name');
            $table->string('assistant_email')->nullable()->after('assistant_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('congregations', function (Blueprint $table) {
            $table->dropColumn(['assistant_name', 'assistant_phone', 'assistant_email']);
        });
    }
};
