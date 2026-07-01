<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration ini menghubungkan jadwal pelajaran dan guru piket dengan tahun ajaran.
return new class extends Migration
{
    public function up(): void
    {
        // Kolom tahun_ajaran_id ditambahkan agar jadwal bisa difilter per periode sekolah.
        foreach (['jadwal_pelajarans', 'guru_pikets'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'tahun_ajaran_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tahun_ajaran_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tahun_ajarans')
                    ->nullOnDelete();
            });
        }

        $aktifId = DB::table('tahun_ajarans')->where('aktif', true)->value('id');

        // Jadwal lama yang belum punya tahun ajaran diisi dengan tahun ajaran aktif.
        if ($aktifId) {
            foreach (['jadwal_pelajarans', 'guru_pikets'] as $tableName) {
                if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tahun_ajaran_id')) {
                    DB::table($tableName)->whereNull('tahun_ajaran_id')->update(['tahun_ajaran_id' => $aktifId]);
                }
            }
        }
    }

    public function down(): void
    {
        // Rollback melepas relasi tahun ajaran dari jadwal dan piket.
        foreach (['jadwal_pelajarans', 'guru_pikets'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'tahun_ajaran_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tahun_ajaran_id');
            });
        }
    }
};
