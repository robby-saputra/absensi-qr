<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan soft delete ke tabel inti agar data tidak langsung hilang permanen.
return new class extends Migration
{
    public function up(): void
    {
        // deleted_at dipakai untuk arsip data pada tabel-tabel utama.
        foreach ([
            'users',
            'kelas',
            'jurusan',
            'jadwal_pelajarans',
            'guru_pikets',
            'absensis',
            'absensi_mapels',
            'kalender_sekolahs',
            'tahun_ajarans',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamp('deleted_at')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        // Rollback menghapus kolom deleted_at dari tabel inti jika ada.
        foreach ([
            'users',
            'kelas',
            'jurusan',
            'jadwal_pelajarans',
            'guru_pikets',
            'absensis',
            'absensi_mapels',
            'kalender_sekolahs',
            'tahun_ajarans',
        ] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('deleted_at');
                });
            }
        }
    }
};
