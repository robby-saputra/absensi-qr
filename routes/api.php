<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\UsersController;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AttendanceSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AUTH (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| QR GENERATOR (PIKET ONLY)
|--------------------------------------------------------------------------
*/
Route::post('/qr/generate', [QrController::class, 'generate'])
    ->middleware('role:piket');

/*
|--------------------------------------------------------------------------
| DATA USERS (PIKET ONLY)
|--------------------------------------------------------------------------
*/
Route::get('/users', [UsersController::class, 'index'])
    ->middleware('role:piket');

/*
|--------------------------------------------------------------------------
| SCAN ABSENSI LAMA
|--------------------------------------------------------------------------
*/
Route::post('/scan', [AbsensiController::class, 'scan'])
    ->middleware('role:siswa');

/*
|--------------------------------------------------------------------------
| SCAN ABSENSI FLUTTER
|--------------------------------------------------------------------------
*/
Route::post('/scan-absensi', function (Request $request) {

    $user = User::find($request->user_id);

    if (! $user) {

        return response()->json([
            'status' => 'error',
            'message' => 'User tidak ditemukan',
        ]);
    }

    $qr = QrCode::where('token', $request->token)
        ->first();

    if (! $qr) {

        return response()->json([
            'status' => 'error',
            'message' => 'QR tidak valid',
        ]);
    }

    $cek = Absensi::where('id_siswa', $user->id)
        ->whereDate('tanggal', now()->toDateString())
        ->first();

    /*
    |--------------------------------------------------------------------------
    | ABSEN MASUK
    |--------------------------------------------------------------------------
    */
    if ($qr->tipe == 'masuk') {

        if ($cek && $cek->jam_masuk) {

            return response()->json([
                'status' => 'error',
                'message' => 'Sudah absen masuk',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CEK STATUS TELAT
        |--------------------------------------------------------------------------
        */
        $jamSekarang = now()->format('H:i:s');

        $batasMasuk = AttendanceSettingService::jamMasuk();

        $statusMasuk = 'hadir';

        if ($jamSekarang > $batasMasuk) {

            $statusMasuk = 'telat';
        }

        if (! $cek) {

            Absensi::create([

                'id_siswa' => $user->id,

                'tanggal' => now()->toDateString(),

                'jam_masuk' => $jamSekarang,

                'status_masuk' => $statusMasuk,
            ]);

        } else {

            $cek->update([

                'jam_masuk' => $jamSekarang,

                'status_masuk' => $statusMasuk,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ABSEN PULANG
    |--------------------------------------------------------------------------
    */
    if ($qr->tipe == 'pulang') {

        if (! $cek) {

            return response()->json([
                'status' => 'error',
                'message' => 'Belum absen masuk',
            ]);
        }

        if ($cek->jam_pulang) {

            return response()->json([
                'status' => 'error',
                'message' => 'Sudah absen pulang',
            ]);
        }

        $cek->update([

            'jam_pulang' => now()->format('H:i:s'),

            'status_pulang' => now()->format('H:i:s') < AttendanceSettingService::jamPulang() ? 'pulang_cepat' : 'pulang',
        ]);
    }

    return response()->json([

        'status' => 'success',

        'message' => 'Absensi berhasil',
    ]);
});

/*
|--------------------------------------------------------------------------
| SCAN ABSENSI MAPEL
|--------------------------------------------------------------------------
*/
Route::post('/scan-mapel', function (Request $request) {
    try {
        $user = User::find($request->user_id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan']);
        } $qr = DB::table('qr_sesis')->where('token', $request->token)->first();
        if (! $qr) {
            return response()->json(['status' => 'error', 'message' => 'QR Mapel tidak valid']);
        } /* |-------------------------------------------------------------------------- | CEK QR AKTIF |-------------------------------------------------------------------------- */ if ($qr->aktif != 1) {
            return response()->json(['status' => 'error', 'message' => 'QR sesi sudah ditutup']);
        } /* |-------------------------------------------------------------------------- | CEK DOUBLE ABSEN |-------------------------------------------------------------------------- */ $cek = DB::table('absensi_mapels')->where('siswa_id', $user->id)->where('jadwal_id', $qr->jadwal_id)->whereDate('tanggal', now()->toDateString())->first();
        if ($cek) {
            return response()->json(['status' => 'error', 'message' => 'Sudah absen mapel ini']);
        } /* |-------------------------------------------------------------------------- | SIMPAN ABSENSI MAPEL |-------------------------------------------------------------------------- */ DB::table('absensi_mapels')->insert(['jadwal_id' => $qr->jadwal_id, 'siswa_id' => $user->id, 'tanggal' => now()->toDateString(), 'jam_scan' => now()->format('H:i:s'), 'status' => 'hadir', 'created_at' => now(), 'updated_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'Absensi mapel berhasil']);
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
});

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| RIWAYAT ABSENSI SISWA
|--------------------------------------------------------------------------
*/
Route::get('/riwayat/{siswa_id}', function ($siswa_id) {

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
});
