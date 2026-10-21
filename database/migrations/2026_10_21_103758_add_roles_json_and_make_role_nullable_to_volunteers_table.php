<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('volunteers', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('name');
        });

        DB::table('volunteers')->orderBy('id')->get()->each(function ($volunteer): void {
            if (! empty($volunteer->role)) {
                DB::table('volunteers')->where('id', $volunteer->id)->update([
                    'roles' => json_encode([$volunteer->role]),
                ]);
            } else {
                DB::table('volunteers')->where('id', $volunteer->id)->update([
                    'roles' => json_encode([]),
                ]);
            }
        });

        Schema::table('volunteers', function (Blueprint $table) {
            $table->string('role')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('volunteers')->whereNotNull('roles')->get()->each(function ($volunteer): void {
            $roles = json_decode($volunteer->roles ?? '[]', true);

            if (is_array($roles) && $roles !== []) {
                DB::table('volunteers')->where('id', $volunteer->id)->update([
                    'role' => $roles[0],
                ]);
            }
        });

        Schema::table('volunteers', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
