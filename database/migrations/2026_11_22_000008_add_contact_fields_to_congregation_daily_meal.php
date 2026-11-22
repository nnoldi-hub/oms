<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregation_daily_meal', function (Blueprint $table): void {
            $table->string('contact_status')->default('necontactat')->after('position')->index();
            $table->timestamp('contacted_at')->nullable()->after('contact_status');
            $table->string('contact_notes', 500)->nullable()->after('contacted_at');
        });
    }

    public function down(): void
    {
        Schema::table('congregation_daily_meal', function (Blueprint $table): void {
            $table->dropColumn(['contact_status', 'contacted_at', 'contact_notes']);
        });
    }
};
