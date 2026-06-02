<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AbsensiRekapSync
{
    public static function harian(?string $tanggal = null, ?int $tahunAjaranId = null): array
    {
        if (! Schema::hasTable('absensis') || ! Schema::hasColumn('absensis', 'tahun_ajaran_id')) {
            return ['updated' => 0, 'message' => 'Kolom tahun ajaran absensi belum tersedia.'];
        }

        $periods = DB::table('tahun_ajarans')
            ->when($tahunAjaranId, fn ($query) => $query->where('id', $tahunAjaranId))
            ->when($tanggal, function ($query) use ($tanggal) {
                $query->whereDate('tanggal_mulai', '<=', $tanggal)
                    ->whereDate('tanggal_selesai', '>=', $tanggal);
            })
            ->orderBy('tanggal_mulai')
            ->get();

        $updated = 0;

        foreach ($periods as $period) {
            $query = DB::table('absensis')
                ->whereNull('tahun_ajaran_id')
                ->whereDate('tanggal', '>=', $period->tanggal_mulai)
                ->whereDate('tanggal', '<=', $period->tanggal_selesai);

            if ($tanggal) {
                $query->whereDate('tanggal', $tanggal);
            }

            $updated += $query->update([
                'tahun_ajaran_id' => $period->id,
                'updated_at' => now(),
            ]);
        }

        return [
            'updated' => $updated,
            'message' => $updated.' data absensi harian berhasil disinkronkan ke rekap.',
        ];
    }
}
