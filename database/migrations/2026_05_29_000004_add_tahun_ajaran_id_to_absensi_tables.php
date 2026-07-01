<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan relasi tahun ajaran ke absensi harian dan absensi mapel.
return new class extends Migration
{
    public function up(): void
    {
        // Kolom tahun_ajaran_id ditambahkan pada dua tabel absensi jika belum tersedia.
        foreach (['absensis', 'absensi_mapels'] as $tableName) {
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

        $periods = DB::table('tahun_ajarans')->orderBy('tanggal_mulai')->get();

        // Data lama disinkronkan ke tahun ajaran berdasarkan tanggal absensi.
        foreach (['absensis' => 'tanggal', 'absensi_mapels' => 'tanggal'] as $tableName => $dateColumn) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'tahun_ajaran_id')) {
                continue;
            }

            foreach ($periods as $period) {
                DB::table($tableName)
                    ->whereNull('tahun_ajaran_id')
                    ->whereDate($dateColumn, '>=', $period->tanggal_mulai)
                    ->whereDate($dateColumn, '<=', $period->tanggal_selesai)
                    ->update(['tahun_ajaran_id' => $period->id]);
            }
        }
    }

    public function down(): void
    {
        // Rollback melepas kolom tahun_ajaran_id dari tabel absensi.
        foreach (['absensis', 'absensi_mapels'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'tahun_ajaran_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tahun_ajaran_id');
            });
        }
    }
};
