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

        foreach ([
            'notif_login_mencurigakan' => '1',
            'notif_login_threshold' => '3',
            'notif_pengajuan_izin_guru' => '1',
            'notif_belum_absen_pulang' => '1',
        ] as $key => $value) {
            DB::table('attendance_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendance_settings')) {
            DB::table('attendance_settings')->whereIn('key', [
                'notif_login_mencurigakan',
                'notif_login_threshold',
                'notif_pengajuan_izin_guru',
                'notif_belum_absen_pulang',
            ])->delete();
        }
    }
};
