<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class SiswaRiwayatController extends Controller
{
    public function index(Request $request, $siswa_id)
    {
        // Data user berasal dari token API.
        // Siswa hanya boleh melihat riwayat miliknya sendiri.
        $user = $request->attributes->get('user_login');
        if (! $user || (int) $user->id !== (int) $siswa_id) {
            return response()->json(['status' => 'error', 'message' => 'Akses data siswa ditolak'], 403);
        }
        /*
        |--------------------------------------------------------------------------
        | ABSENSI HARIAN
        |--------------------------------------------------------------------------
        */
        // Mengambil data absensi harian siswa yang belum diarsipkan.
        $harian = DB::table('absensis')

            ->where('id_siswa', $siswa_id)
            ->whereNull('deleted_at')

            ->get()

            ->flatMap(function ($item) {

                // Satu record absensi harian bisa menghasilkan dua riwayat:
                // absen masuk dan absen pulang.
                $data = [];

                /*
                |--------------------------------------------------------------------------
                | ABSEN MASUK
                |--------------------------------------------------------------------------
                */
                if ($item->jam_masuk) {

                    // Riwayat absen masuk ditampilkan jika jam_masuk terisi.
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

                    // Riwayat absen pulang ditampilkan jika jam_pulang terisi.
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
        // Mengambil data absensi mata pelajaran siswa.
        $mapel = DB::table('absensi_mapels')

            ->where('siswa_id', $siswa_id)
            ->whereNull('deleted_at')

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
        // Riwayat harian dan mapel digabung lalu diurutkan dari tanggal terbaru.
        $riwayat = collect($harian)

            ->merge(collect($mapel))

            ->sortByDesc('tanggal')

            ->values();

        // Response dikirim dalam bentuk array riwayat untuk ditampilkan di aplikasi mobile.
        return response()->json($riwayat);
    }
}
