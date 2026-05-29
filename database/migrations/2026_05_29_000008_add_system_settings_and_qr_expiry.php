<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('qr_codes') && ! Schema::hasColumn('qr_codes', 'expires_at')) {
            Schema::table('qr_codes', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('token');
            });
        }

        if (Schema::hasTable('qr_sesis') && ! Schema::hasColumn('qr_sesis', 'expires_at')) {
            Schema::table('qr_sesis', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('token');
            });
        }

        $defaults = [
            'batas_telat' => '07:15:00',
            'masa_aktif_qr' => '30',
            'status_default_alfa' => 'alfa',
            'nama_sekolah' => 'Absensi QR',
            'logo_sekolah' => 'img/logo-ba.png',
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
        if (Schema::hasTable('qr_codes') && Schema::hasColumn('qr_codes', 'expires_at')) {
            Schema::table('qr_codes', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }

        if (Schema::hasTable('qr_sesis') && Schema::hasColumn('qr_sesis', 'expires_at')) {
            Schema::table('qr_sesis', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
        }

        DB::table('attendance_settings')
            ->whereIn('key', ['batas_telat', 'masa_aktif_qr', 'status_default_alfa', 'nama_sekolah', 'logo_sekolah'])
            ->delete();
    }
};
