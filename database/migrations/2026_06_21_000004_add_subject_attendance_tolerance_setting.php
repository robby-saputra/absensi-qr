<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_settings')) {
            DB::table('attendance_settings')->updateOrInsert(
                ['key' => 'toleransi_telat_mapel_menit'],
                ['value' => '10', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_settings')) {
            DB::table('attendance_settings')->where('key', 'toleransi_telat_mapel_menit')->delete();
        }
    }
};
