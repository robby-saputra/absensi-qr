<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
