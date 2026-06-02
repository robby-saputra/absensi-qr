<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SiswaRiwayatController extends Controller
{
    public function index($siswa_id)
    {
        /*
        |--------------------------------------------------------------------------
        | ABSENSI HARIAN
        |--------------------------------------------------------------------------
        */
        $harian = DB::table('absensis')

            ->where('id_siswa', $siswa_id)

            ->get()

            ->flatMap(function ($item) {

                $data = [];

                /*
                |--------------------------------------------------------------------------
                | ABSEN MASUK
                |--------------------------------------------------------------------------
                */
                if ($item->jam_masuk) {

                    $data[] = [

                        'tanggal' => $item->tanggal,

                        'jam_scan' => $item->jam_masuk,

                        'jenis' => 'Absen Masuk',

                        'status' => $item->status_masuk,
                    ];
                }

                /*
                |--------------------------------------------------------------------------
                | ABSEN PULANG
                |--------------------------------------------------------------------------
                */
                if ($item->jam_pulang) {

                    $data[] = [

                        'tanggal' => $item->tanggal,

                        'jam_scan' => $item->jam_pulang,

                        'jenis' => 'Absen Pulang',

                        'status' => $item->status_pulang,
                    ];
                }

                return $data;
            });

        /*
        |--------------------------------------------------------------------------
        | ABSENSI MAPEL
        |--------------------------------------------------------------------------
        */
        $mapel = DB::table('absensi_mapels')

            ->where('siswa_id', $siswa_id)

            ->select(

                'tanggal',

                'jam_scan',

                'status',

                DB::raw("'Absensi Mapel' as jenis")
            )

            ->get();
        /*
        |--------------------------------------------------------------------------
        | GABUNGKAN DATA
        |--------------------------------------------------------------------------
        */
        $riwayat = collect($harian)

            ->merge(collect($mapel))

            ->sortByDesc('tanggal')

            ->values();

        return response()->json($riwayat);
    }
}
