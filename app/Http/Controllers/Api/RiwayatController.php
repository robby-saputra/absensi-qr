<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class RiwayatController extends Controller
{
    public function index($siswa_id)
    {

        // ABSENSI HARIAN

        $harian = DB::table('absensis')
            ->where('siswa_id', $siswa_id)
            ->select(
                'tanggal',
                'status',
                DB::raw("'Absensi Harian' as jenis")
            )
            ->get();

        // ABSENSI MAPEL

        $mapel = DB::table('absensi_mapels')
            ->where('siswa_id', $siswa_id)
            ->select(
                'tanggal',
                'status',
                DB::raw("'Absensi Mapel' as jenis")
            )
            ->get();

        // GABUNGKAN

        $riwayat = $harian
            ->merge($mapel)
            ->sortByDesc('tanggal')
            ->values();

        return response()->json($riwayat);
    }
}
