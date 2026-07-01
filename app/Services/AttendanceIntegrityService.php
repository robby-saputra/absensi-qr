<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Service ini memeriksa kesehatan data absensi agar tidak ada data ganda atau data yatim.
class AttendanceIntegrityService
{
    // Audit menghitung masalah data tanpa mengubah isi database.
    public function audit(): array
    {
        return [
            'duplicate_daily' => $this->duplicates('absensis', ['id_siswa', 'tanggal']),
            'duplicate_subject' => $this->duplicates('absensi_mapels', ['siswa_id', 'jadwal_id', 'tanggal']),
            'duplicate_duty_status' => $this->duplicates('guru_piket_statuses', ['guru_piket_id', 'guru_id', 'tanggal']),
            'orphan_subject' => Schema::hasTable('absensi_mapels') ? DB::table('absensi_mapels as a')->leftJoin('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')->whereNull('a.deleted_at')->whereNull('j.id')->count() : 0,
            'legacy_duty_status_without_date' => Schema::hasTable('guru_pikets') ? DB::table('guru_pikets')->whereNotNull('status_dipilih_at')->whereNull('deleted_at')->count() : 0,
        ];
    }

    // Repair saat ini bersifat aman: hanya melaporkan hasil audit dan tidak menebak data yang ambigu.
    public function repair(bool $dryRun = true): array
    {
        $result = $this->audit();
        $result['dry_run'] = $dryRun;
        // Unique active indexes already prevent new duplicates. Existing ambiguous
        // records are intentionally not guessed or deleted by this command.
        $result['changed'] = 0;

        return $result;
    }

    // Menghitung kombinasi kolom yang muncul lebih dari sekali pada tabel tertentu.
    private function duplicates(string $table, array $columns): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        $query = DB::table($table)->select($columns)->selectRaw('COUNT(*) as total')->groupBy($columns)->havingRaw('COUNT(*) > 1');
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return DB::query()->fromSub($query, 'duplicates')->count();
    }
}
