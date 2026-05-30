<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
