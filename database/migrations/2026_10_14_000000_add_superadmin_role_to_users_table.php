<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY role ENUM('superadmin', 'admin', 'pelanggan') NOT NULL DEFAULT 'pelanggan'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'pelanggan') NOT NULL DEFAULT 'pelanggan'");
        }
    }
};
