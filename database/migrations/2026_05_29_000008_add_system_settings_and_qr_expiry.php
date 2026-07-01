<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan masa berlaku QR dan pengaturan dasar sistem absensi.
return new class extends Migration
{
    public function up(): void
    {
        // QR harian diberi expires_at agar token scan tidak aktif selamanya.
        if (Schema::hasTable('qr_codes') && ! Schema::hasColumn('qr_codes', 'expires_at')) {
            Schema::table('qr_codes', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('token');
            });
        }

        // QR sesi mapel juga diberi masa berlaku.
        if (Schema::hasTable('qr_sesis') && ! Schema::hasColumn('qr_sesis', 'expires_at')) {
            Schema::table('qr_sesis', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('token');
            });
        }

        // Default setting dipakai sistem jika admin belum mengubah pengaturan.
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
        // Rollback menghapus kolom expires_at dan setting default yang ditambahkan migration ini.
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
