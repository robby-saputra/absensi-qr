<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan setting toleransi telat untuk absensi mapel.
return new class extends Migration
{
    public function up(): void
    {
        // Nilai default 10 menit dipakai untuk menentukan status telat mapel.
        if (Schema::hasTable('attendance_settings')) {
            DB::table('attendance_settings')->updateOrInsert(
                ['key' => 'toleransi_telat_mapel_menit'],
                ['value' => '10', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Rollback menghapus setting toleransi telat mapel.
        if (Schema::hasTable('attendance_settings')) {
            DB::table('attendance_settings')->where('key', 'toleransi_telat_mapel_menit')->delete();
        }
    }
};
