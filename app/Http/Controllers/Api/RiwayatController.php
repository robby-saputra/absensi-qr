<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

// Controller API ini menggabungkan riwayat absensi harian dan absensi mapel siswa.
class RiwayatController extends Controller
{
    // Mengambil riwayat absensi berdasarkan ID siswa lalu mengurutkannya dari tanggal terbaru.
    public function index($siswa_id)
    {

        // Mengambil data absensi harian siswa.

        $harian = DB::table('absensis')
            ->where('siswa_id', $siswa_id)
            ->select(
                'tanggal',
                'status',
                DB::raw("'Absensi Harian' as jenis")
            )
            ->get();

        // Mengambil data absensi mapel siswa.

        $mapel = DB::table('absensi_mapels')
            ->where('siswa_id', $siswa_id)
            ->select(
                'tanggal',
                'status',
                DB::raw("'Absensi Mapel' as jenis")
            )
            ->get();

        // Menggabungkan dua sumber riwayat lalu mengurutkan tanggal terbaru di atas.

        $riwayat = $harian
            ->merge($mapel)
            ->sortByDesc('tanggal')
            ->values();

        return response()->json($riwayat);
    }
}
