<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_settings')) {
            return;
        }

        if (DB::table('attendance_settings')->where('key', 'jam_kunci_absensi')->exists()) {
            return;
        }

        DB::table('attendance_settings')->insert([
            'key' => 'jam_kunci_absensi',
            'value' => '14:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendance_settings')) {
            return;
        }

        DB::table('attendance_settings')->where('key', 'jam_kunci_absensi')->delete();
    }
};
