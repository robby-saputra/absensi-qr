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
        if (! Schema::hasTable('attendance_settings')) {
            return;
        }

        DB::table('attendance_settings')
            ->whereIn('key', ['latitude_sekolah', 'longitude_sekolah', 'radius_absensi'])
            ->delete();
    }
};
