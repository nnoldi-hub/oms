<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'coordinator', 'construction', 'kitchen', 'supply_manager', 'congregation_responsible', 'project_supervisor') NOT NULL DEFAULT 'coordinator'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'coordinator', 'construction', 'kitchen') NOT NULL DEFAULT 'coordinator'");
        }
    }
};
