<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan setting lokasi sekolah untuk validasi radius absensi.
return new class extends Migration
{
    public function up(): void
    {
        // Latitude, longitude, dan radius default disimpan ke attendance_settings.
        if (! Schema::hasTable('attendance_settings')) {
            return;
        }

        $defaults = [
            'latitude_sekolah' => '-6.172564',
            'longitude_sekolah' => '106.627565',
            'radius_absensi' => '200',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('attendance_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Rollback menghapus setting lokasi absensi.
        if (! Schema::hasTable('attendance_settings')) {
            return;
        }

        DB::table('attendance_settings')
            ->whereIn('key', ['latitude_sekolah', 'longitude_sekolah', 'radius_absensi'])
            ->delete();
    }
};
