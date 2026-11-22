<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table): void {
            $table->string('kind')->default('alimente')->after('status')->index();
            $table->date('date')->nullable()->after('kind')->index();
            $table->decimal('amount_ron', 12, 2)->default(0)->after('estimated_value');
            $table->foreignId('donor_congregation_id')->nullable()->after('congregation_id')->constrained('congregations')->nullOnDelete();
        });

        DB::table('donations')->update([
            'date' => DB::raw('donation_date'),
            'amount_ron' => DB::raw('estimated_value'),
            'kind' => 'alimente',
        ]);

        DB::table('donations')->where('status', 'promised')->update(['status' => 'promisa']);
        DB::table('donations')->where('status', 'received')->update(['status' => 'primita']);
    }

    public function down(): void
    {
        DB::table('donations')->where('status', 'promisa')->update(['status' => 'promised']);
        DB::table('donations')->where('status', 'primita')->update(['status' => 'received']);

        Schema::table('donations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('donor_congregation_id');
            $table->dropColumn(['kind', 'date', 'amount_ron']);
        });
    }
};
