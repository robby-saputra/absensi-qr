<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan level admin dan menetapkan admin pertama sebagai superadmin.
return new class extends Migration
{
    public function up(): void
    {
        // Kolom admin_level membedakan superadmin dan admin biasa.
        if (! Schema::hasColumn('users', 'admin_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('admin_level', 30)->nullable()->after('role');
            });
        }

        // Admin pertama dijadikan superadmin awal agar fitur sensitif punya pemilik.
        $admin = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->first();

        if ($admin) {
            DB::table('users')
                ->where('id', $admin->id)
                ->update([
                    'nama' => 'Devi',
                    'admin_level' => 'superadmin',
                    'aktif' => 1,
                    'updated_at' => now(),
                ]);

            DB::table('users')
                ->where('role', 'admin')
                ->where('id', '!=', $admin->id)
                ->update([
                    'admin_level' => null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Rollback menghapus kolom admin_level.
        if (Schema::hasColumn('users', 'admin_level')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('admin_level');
            });
        }
    }
};
