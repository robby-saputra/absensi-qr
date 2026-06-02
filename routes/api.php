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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

if (! function_exists('apiKalenderSiswa')) {
    function apiKalenderSiswa(string $mulai, string $selesai)
    {
        $events = DB::table('kalender_sekolahs')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($mulai, $selesai) {
                $query->where(function ($range) use ($mulai, $selesai) {
                    $range->whereDate('tanggal_mulai', '<=', $selesai)
                        ->whereDate('tanggal_selesai', '>=', $mulai);
                })->orWhere('berulang', 1);
            })
            ->orderBy('tanggal_mulai')
            ->get();

        $result = collect();
        $start = \Carbon\Carbon::parse($mulai);
        $end = \Carbon\Carbon::parse($selesai);

        foreach ($events as $event) {
            if ((int) ($event->berulang ?? 0) === 1 && $event->hari_berulang) {
                $cursor = $start->copy();
                while ($cursor->lte($end)) {
                    $hari = strtolower($cursor->locale('id')->translatedFormat('l'));
                    if ($hari === strtolower($event->hari_berulang)) {
                        $result->push([
                            'id' => $event->id,
                            'tanggal_mulai' => $cursor->toDateString(),
                            'tanggal_selesai' => $cursor->toDateString(),
                            'judul' => $event->judul,
                            'jenis' => $event->jenis,
                            'provinsi' => $event->provinsi,
                            'keterangan' => $event->keterangan,
                            'berulang' => true,
                        ]);
                    }
                    $cursor->addDay();
                }
            } else {
                $result->push([
                    'id' => $event->id,
                    'tanggal_mulai' => $event->tanggal_mulai,
                    'tanggal_selesai' => $event->tanggal_selesai,
                    'judul' => $event->judul,
                    'jenis' => $event->jenis,
                    'provinsi' => $event->provinsi,
                    'keterangan' => $event->keterangan,
                    'berulang' => false,
                ]);
            }
        }

        return $result->unique(fn ($item) => $item['id'].'-'.$item['tanggal_mulai'])
            ->sortBy('tanggal_mulai')
            ->values();
    }
}

if (! function_exists('kirimNotifikasiOrangTua')) {
    function kirimNotifikasiOrangTua(int $siswaId, string $judul, string $pesan, array $data = []): void
    {
        $tokens = DB::table('parent_fcm_tokens')
            ->where('siswa_id', $siswaId)
            ->pluck('token')
            ->filter()
            ->values();

        if ($tokens->isEmpty()) {
            return;
        }

        DB::table('notifications')->insert([
            'user_id' => $siswaId,
            'judul' => $judul,
            'pesan' => $pesan,
            'kategori' => 'orang_tua',
            'status' => 'belum_dibaca',
            'source_type' => 'fcm_orang_tua',
            'source_id' => $siswaId,
            'payload' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accessToken = fcmAccessToken();
        $projectId = fcmProjectId();
        if (! $accessToken || ! $projectId) {
            return;
        }

        foreach ($tokens as $token) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $judul,
                            'body' => $pesan,
                        ],
                        'data' => array_merge([
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'siswa_id' => (string) $siswaId,
                        ], collect($data)->map(fn ($value) => (string) $value)->all()),
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => [
                                'channel_id' => 'orang_tua_absensi',
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ]);

                if (! $response->successful()) {
                    Log::warning('FCM orang tua gagal dikirim', [
                        'siswa_id' => $siswaId,
                        'status' => $response->status(),
                        'body' => $response->json() ?: $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}

if (! function_exists('apiBuatNotifikasiAdmin')) {
    function apiBuatNotifikasiAdmin(string $kategori, string $judul, string $pesan, array $payload = [], string $severity = 'info'): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        DB::table('notifications')->insert([
            'user_id' => null,
            'judul' => $judul,
            'pesan' => $pesan,
            'kategori' => $kategori,
            'severity' => $severity,
            'status' => 'belum_dibaca',
            'source_type' => $payload['source_type'] ?? 'api_absensi',
            'source_id' => $payload['source_id'] ?? null,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

if (! function_exists('fcmServiceAccount')) {
    function fcmServiceAccount(): ?array
    {
        $path = config('services.fcm.service_account_path');
        if (! $path || ! file_exists($path)) {
            return null;
        }

        $account = json_decode(file_get_contents($path), true);

        return is_array($account) ? $account : null;
    }
}

if (! function_exists('fcmProjectId')) {
    function fcmProjectId(): ?string
    {
        return fcmServiceAccount()['project_id'] ?? null;
    }
}

if (! function_exists('fcmAccessToken')) {
    function fcmAccessToken(): ?string
    {
        $account = fcmServiceAccount();
        if (! $account || empty($account['client_email']) || empty($account['private_key'])) {
            return null;
        }

        return Cache::remember('fcm_http_v1_access_token', 3300, function () use ($account) {
            $now = fcmTrustedNow();
            $header = ['alg' => 'RS256', 'typ' => 'JWT'];
            $claim = [
                'iss' => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $segments = [
                rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '='),
                rtrim(strtr(base64_encode(json_encode($claim)), '+/', '-_'), '='),
            ];
            $payload = implode('.', $segments);

            openssl_sign($payload, $signature, $account['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $payload.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            try {
                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
            } catch (\Throwable $e) {
                report($e);

                return null;
            }

            if (! $response->successful()) {
                Log::warning('FCM access token gagal dibuat', [
                    'status' => $response->status(),
                    'body' => $response->json() ?: $response->body(),
                ]);

                return null;
            }

            return $response->json('access_token');
        });
    }
}

if (! function_exists('fcmTrustedNow')) {
    function fcmTrustedNow(): int
    {
        try {
            $response = Http::timeout(5)->get('https://oauth2.googleapis.com');
            $date = $response->header('Date');
            if ($date && strtotime($date)) {
                return strtotime($date) - 60;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return time();
    }
}

if (! function_exists('apiValidasiLokasiSekolah')) {
    function apiValidasiLokasiSekolah(Request $request)
    {
        $lat = $request->input('latitude');
        $lng = $request->input('longitude');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lokasi belum terkirim. Aktifkan GPS dan izinkan akses lokasi sebelum scan absensi.',
            ]);
        }

      $schoolLat = -6.172564;
$schoolLng = 106.627565;
$allowedRadius = 300;
$earthRadius = 6371000;

        $dLat = deg2rad((float) $lat - $schoolLat);
        $dLng = deg2rad((float) $lng - $schoolLng);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($schoolLat)) * cos(deg2rad((float) $lat)) * sin($dLng / 2) ** 2;
        $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));

        if ($distance > $allowedRadius) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda berada sekitar '.round($distance).' meter dari sekolah. Scan hanya bisa dilakukan dalam radius '.$allowedRadius.' meter dari SMK Bhakti Anindya.',
                'distance_meters' => round($distance, 2),
                'allowed_radius_meters' => $allowedRadius,
            ]);
        }

        return null;
    }
}

/*
|--------------------------------------------------------------------------
| AUTH (PUBLIC)
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

Route::post('/fcm/register-parent', function (Request $request) {
    $request->validate([
        'siswa_id' => 'required|exists:users,id',
        'token' => 'required|string|max:500',
        'device_name' => 'nullable|string|max:120',
    ]);

    DB::table('parent_fcm_tokens')->updateOrInsert(
        ['token' => $request->token],
        [
            'siswa_id' => $request->siswa_id,
            'device_name' => $request->device_name,
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    return response()->json([
        'status' => 'success',
        'message' => 'Token notifikasi orang tua tersimpan.',
    ]);
});

/*
|--------------------------------------------------------------------------
| QR GENERATOR (PIKET ONLY)
|--------------------------------------------------------------------------
*/
Route::post('/qr/generate', [QrController::class, 'generate'])
    ->middleware('role:piket,guru');

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

    if ($lokasiError = apiValidasiLokasiSekolah($request)) {
        return $lokasiError;
    }

    $qr = QrCode::where('token', $request->token)
        ->first();

    if (! $qr) {

        return response()->json([
            'status' => 'error',
            'message' => 'QR tidak valid',
        ]);
    }

    $hari = strtolower(now()->locale('id')->translatedFormat('l'));
    $libur = DB::table('kalender_sekolahs')
        ->where('jenis', 'libur')
        ->where(function ($query) use ($hari) {
            $query->where(function ($date) {
                $date->whereDate('tanggal_mulai', '<=', now()->toDateString())
                    ->whereDate('tanggal_selesai', '>=', now()->toDateString());
            })->orWhere(function ($repeat) use ($hari) {
                $repeat->where('berulang', 1)->where('hari_berulang', $hari);
            });
        })
        ->first();

    if ($libur) {
        return response()->json([
            'status' => 'error',
            'code' => 'hari_libur',
            'title' => 'Hari Ini Libur',
            'message' => 'Hari ini libur: '.$libur->judul.'. Absensi tidak dibuka dan tidak dihitung alfa.',
            'should_redirect' => true,
            'redirect_to' => 'dashboard',
            'route' => '/dashboard/users',
            'libur' => [
                'judul' => $libur->judul,
                'tanggal' => now()->toDateString(),
            ],
        ]);
    }

    if ($qr->expires_at && now()->greaterThan($qr->expires_at)) {
        return response()->json([
            'status' => 'error',
            'message' => 'QR sudah kedaluwarsa',
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

        $batasMasuk = AttendanceSettingService::batasTelat();

        $statusMasuk = 'hadir';

        if ($jamSekarang > $batasMasuk) {

            $statusMasuk = 'telat';
        }

        $tahunAjaranId = DB::table('tahun_ajarans')->where('aktif', true)->value('id');

        if (! $cek) {

            Absensi::create([

                'id_siswa' => $user->id,

                'tahun_ajaran_id' => $tahunAjaranId,

                'tanggal' => now()->toDateString(),

                'jam_masuk' => $jamSekarang,

                'status_masuk' => $statusMasuk,
            ]);

        } else {

            $cek->update([

                'tahun_ajaran_id' => $cek->tahun_ajaran_id ?: $tahunAjaranId,

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

            'tahun_ajaran_id' => $cek->tahun_ajaran_id ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id'),

            'jam_pulang' => now()->format('H:i:s'),

            'status_pulang' => now()->format('H:i:s') < AttendanceSettingService::jamPulang() ? 'pulang_cepat' : 'pulang',
        ]);
    }

    if ($qr->tipe === 'masuk') {
        if ((DB::table('attendance_settings')->where('key', 'notif_absen_masuk_admin')->value('value') ?? '1') === '1') {
            $kelasSiswa = DB::table('kelas')->where('id', $user->kelas_id)->value('nama_kelas');
            apiBuatNotifikasiAdmin(
                'absensi_masuk_siswa',
                'Siswa Absen Masuk',
                $user->nama.' sudah absen masuk pukul '.now()->format('H:i').' dengan status '.($statusMasuk ?? 'hadir').'.',
                [
                    'source_id' => $user->id,
                    'siswa_id' => $user->id,
                    'siswa' => $user->nama,
                    'nis' => $user->nis,
                    'kelas' => $kelasSiswa ?: '-',
                    'jam' => now()->format('H:i:s'),
                    'status' => $statusMasuk ?? 'hadir',
                    'tanggal' => now()->toDateString(),
                ],
                ($statusMasuk ?? 'hadir') === 'telat' ? 'warning' : 'success'
            );
        }

        kirimNotifikasiOrangTua(
            (int) $user->id,
            'Absensi Masuk',
            $user->nama.' sudah absen masuk jam '.now()->format('H:i').' dengan status '.($statusMasuk ?? 'hadir').'.',
            ['tipe' => 'masuk', 'jam' => now()->format('H:i'), 'status' => $statusMasuk ?? 'hadir']
        );
    }

    if ($qr->tipe === 'pulang') {
        kirimNotifikasiOrangTua(
            (int) $user->id,
            'Absensi Pulang',
            $user->nama.' sudah absen pulang jam '.now()->format('H:i').'.',
            ['tipe' => 'pulang', 'jam' => now()->format('H:i')]
        );
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
        } if ($lokasiError = apiValidasiLokasiSekolah($request)) {
            return $lokasiError;
        } $qr = DB::table('qr_sesis')->where('token', $request->token)->first();
        if (! $qr) {
            return response()->json(['status' => 'error', 'message' => 'QR Mapel tidak valid']);
        } $hari = strtolower(now()->locale('id')->translatedFormat('l'));
        $libur = DB::table('kalender_sekolahs')
            ->where('jenis', 'libur')
            ->where(function ($query) use ($hari) {
                $query->where(function ($date) {
                    $date->whereDate('tanggal_mulai', '<=', now()->toDateString())
                        ->whereDate('tanggal_selesai', '>=', now()->toDateString());
                })->orWhere(function ($repeat) use ($hari) {
                    $repeat->where('berulang', 1)->where('hari_berulang', $hari);
                });
            })
            ->first();
        if ($libur) {
            return response()->json([
                'status' => 'error',
                'code' => 'hari_libur',
                'title' => 'Hari Ini Libur',
                'message' => 'Hari ini libur: '.$libur->judul.'. Absensi mapel tidak dibuka dan tidak dihitung alfa.',
                'should_redirect' => true,
                'redirect_to' => 'dashboard',
                'route' => '/dashboard/users',
                'libur' => [
                    'judul' => $libur->judul,
                    'tanggal' => now()->toDateString(),
                ],
            ]);
        } if (($qr->expires_at ?? null) && now()->greaterThan($qr->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'QR Mapel sudah kedaluwarsa']);
        } /* |-------------------------------------------------------------------------- | CEK QR AKTIF |-------------------------------------------------------------------------- */ if ($qr->aktif != 1) {
            return response()->json(['status' => 'error', 'message' => 'QR sesi sudah ditutup']);
        } /* |-------------------------------------------------------------------------- | CEK DOUBLE ABSEN |-------------------------------------------------------------------------- */ $cek = DB::table('absensi_mapels')->where('siswa_id', $user->id)->where('jadwal_id', $qr->jadwal_id)->whereDate('tanggal', now()->toDateString())->first();
        if ($cek) {
            return response()->json(['status' => 'error', 'message' => 'Sudah absen mapel ini']);
        }

        /* |--------------------------------------------------------------------------
        | SIMPAN ABSENSI MAPEL
        |-------------------------------------------------------------------------- */
        $jadwalTahunAjaranId = DB::table('jadwal_pelajarans')
            ->where('id', $qr->jadwal_id)
            ->value('tahun_ajaran_id') ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id');

        DB::table('absensi_mapels')->insert([
            'tahun_ajaran_id' => $jadwalTahunAjaranId,
            'jadwal_id' => $qr->jadwal_id,
            'siswa_id' => $user->id,
            'tanggal' => now()->toDateString(),
            'jam_scan' => now()->format('H:i:s'),
            'status' => 'hadir',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->leftJoin('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->where('j.id', $qr->jadwal_id)
            ->select('m.nama_mapel', 'j.jam_mulai', 'j.jam_selesai')
            ->first();

        kirimNotifikasiOrangTua(
            (int) $user->id,
            'Absensi Mapel',
            $user->nama.' sudah absen mapel '.($jadwal->nama_mapel ?? '-').' jam '.now()->format('H:i').'.',
            ['tipe' => 'mapel', 'mapel' => $jadwal->nama_mapel ?? '-', 'jam' => now()->format('H:i')]
        );

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

Route::get('/siswa/dashboard/{siswa_id}', function ($siswa_id) {
    $user = User::where('role', 'siswa')->find($siswa_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
    }

    $tanggal = now()->toDateString();
    $hari = strtolower(now()->locale('id')->translatedFormat('l'));
    $absensi = DB::table('absensis')->where('id_siswa', $user->id)->whereDate('tanggal', $tanggal)->first();
    $kelas = DB::table('kelas as k')
        ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
        ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')
        ->where('k.id', $user->kelas_id)
        ->select('k.id', 'k.nama_kelas', 'j.nama_jurusan', 'w.nama as wali_kelas')
        ->first();

    $jadwalHariIni = DB::table('jadwal_pelajarans as jp')
        ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
        ->join('users as g', 'g.id', '=', 'jp.guru_id')
        ->leftJoin('users as gp', 'gp.id', '=', 'jp.guru_pengganti_id')
        ->leftJoin('absensi_mapels as am', function ($join) use ($user, $tanggal) {
            $join->on('am.jadwal_id', '=', 'jp.id')
                ->where('am.siswa_id', $user->id)
                ->whereDate('am.tanggal', $tanggal)
                ->whereNull('am.deleted_at');
        })
        ->where('jp.kelas_id', $user->kelas_id)
        ->whereRaw('LOWER(jp.hari) = ?', [$hari])
        ->whereNull('jp.deleted_at')
        ->select(
            'jp.id',
            'jp.hari',
            'jp.jam_mulai',
            'jp.jam_selesai',
            'jp.status_guru',
            'jp.alasan_tidak_hadir',
            'm.nama_mapel',
            'g.nama as guru_utama',
            'gp.nama as guru_pengganti',
            'am.id as absensi_mapel_id',
            'am.jam_scan',
            'am.status as status_absen',
            'am.catatan_guru'
        )
        ->orderBy('jp.jam_mulai')
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'hari' => $item->hari,
                'jam_mulai' => substr((string) $item->jam_mulai, 0, 5),
                'jam_selesai' => substr((string) $item->jam_selesai, 0, 5),
                'nama_mapel' => $item->nama_mapel,
                'guru_utama' => $item->guru_utama,
                'guru_pengganti' => $item->guru_pengganti,
                'status_guru' => $item->status_guru ?: 'normal',
                'alasan_tidak_hadir' => $item->alasan_tidak_hadir,
                'sudah_absen' => $item->absensi_mapel_id ? true : false,
                'jam_scan' => $item->jam_scan ? substr((string) $item->jam_scan, 0, 5) : null,
                'status_absen' => $item->status_absen ?: 'belum',
                'catatan_guru' => $item->catatan_guru,
            ];
        });

    $totalMapel = $jadwalHariIni->count();
    $sudahMapel = $jadwalHariIni->where('sudah_absen', true)->count();
    $kalenderHariIni = apiKalenderSiswa($tanggal, $tanggal);
    $liburHariIni = $kalenderHariIni->first(fn ($event) => ($event['jenis'] ?? null) === 'libur');
    $kalenderMendatang = apiKalenderSiswa(
        now()->startOfMonth()->toDateString(),
        now()->copy()->endOfMonth()->toDateString()
    );

    $pengajuan = DB::table('student_permit_requests as p')
        ->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')
        ->where('p.siswa_id', $user->id)
        ->whereNull('p.deleted_at')
        ->select(
            'p.id',
            'p.tanggal_mulai',
            'p.tanggal_selesai',
            'p.jenis',
            'p.alasan',
            'p.bukti_path',
            'p.status',
            'p.catatan_review',
            'p.reviewed_at',
            'p.created_at',
            'r.nama as reviewer'
        )
        ->latest('p.id')
        ->limit(10)
        ->get()
        ->map(function ($item) {
            $item->bukti_url = $item->bukti_path ? url('storage/'.$item->bukti_path) : null;
            return $item;
        });

    $notifikasi = collect();
    $batasAbsenMasuk = AttendanceSettingService::batasTelat();
    $labelBatasAbsenMasuk = substr((string) $batasAbsenMasuk, 0, 5);
    $izinHariIni = $pengajuan->first(function ($item) use ($tanggal) {
        return $item->status === 'disetujui'
            && $item->tanggal_mulai <= $tanggal
            && $item->tanggal_selesai >= $tanggal;
    });

    if ($liburHariIni) {
        $notifikasi->push([
            'kategori' => 'kalender',
            'judul' => 'Hari Ini Libur',
            'pesan' => $liburHariIni['judul'].'. Absensi hari ini tidak dihitung alfa.',
            'status' => 'belum_dibaca',
            'warna' => 'red',
            'payload' => [
                'tipe' => 'libur',
                'status' => 'libur',
                'tanggal' => $tanggal,
                'judul' => $liburHariIni['judul'],
            ],
        ]);
    } elseif (! ($absensi && $absensi->jam_masuk)) {
        $statusAbsensi = strtolower((string) ($absensi->status_masuk ?? $absensi->status_pulang ?? ''));
        $jenisIzin = strtolower((string) ($izinHariIni->jenis ?? ''));

        if (in_array($statusAbsensi, ['izin', 'sakit'], true) || in_array($jenisIzin, ['izin', 'sakit'], true)) {
            $jenis = in_array($statusAbsensi, ['izin', 'sakit'], true) ? $statusAbsensi : $jenisIzin;
            $notifikasi->push([
                'kategori' => 'absensi_harian',
                'judul' => 'Anak '.ucfirst($jenis),
                'pesan' => $user->nama.' hari ini tercatat '.ucfirst($jenis).'.',
                'status' => 'belum_dibaca',
                'warna' => 'blue',
                'payload' => [
                    'tipe' => $jenis,
                    'status' => $jenis,
                    'jam' => '-',
                    'batas_absen' => $labelBatasAbsenMasuk,
                ],
            ]);
        } elseif (now()->format('H:i:s') > $batasAbsenMasuk) {
            $notifikasi->push([
                'kategori' => 'absensi_harian',
                'judul' => 'Anak Alfa',
                'pesan' => $user->nama.' belum absen masuk sampai batas waktu '.$labelBatasAbsenMasuk.', sehingga sementara dinyatakan Alfa.',
                'status' => 'belum_dibaca',
                'warna' => 'red',
                'payload' => [
                    'tipe' => 'alfa',
                    'status' => AttendanceSettingService::statusDefaultAlfa(),
                    'jam' => '-',
                    'batas_absen' => $labelBatasAbsenMasuk,
                ],
            ]);
        } else {
            $notifikasi->push([
                'kategori' => 'absensi_harian',
                'judul' => 'Belum Absen Masuk',
                'pesan' => $user->nama.' belum absen masuk. Batas absen masuk pukul '.$labelBatasAbsenMasuk.'.',
                'status' => 'belum_dibaca',
                'warna' => 'orange',
                'payload' => [
                    'tipe' => 'masuk',
                    'status' => 'belum_absen',
                    'jam' => '-',
                    'batas_absen' => $labelBatasAbsenMasuk,
                ],
            ]);
        }
    }

    if (! $liburHariIni && $absensi && $absensi->jam_masuk && ! $absensi->jam_pulang) {
        $notifikasi->push([
            'kategori' => 'absensi_harian',
            'judul' => 'Belum Absen Pulang',
            'pesan' => 'Jangan lupa scan QR pulang sebelum pulang sekolah.',
            'status' => 'belum_dibaca',
            'warna' => 'blue',
        ]);
    }

    if (! $liburHariIni && max(0, $totalMapel - $sudahMapel) > 0) {
        $notifikasi->push([
            'kategori' => 'absensi_mapel',
            'judul' => 'Belum Absen Mapel',
            'pesan' => max(0, $totalMapel - $sudahMapel).' sesi mapel hari ini belum discan.',
            'status' => 'belum_dibaca',
            'warna' => 'purple',
        ]);
    }

    foreach ($pengajuan->whereIn('status', ['disetujui', 'ditolak'])->take(3) as $item) {
        $notifikasi->push([
            'kategori' => 'pengajuan_izin',
            'judul' => 'Pengajuan '.ucfirst($item->status),
            'pesan' => 'Pengajuan '.$item->jenis.' tanggal '.$item->tanggal_mulai.' sampai '.$item->tanggal_selesai.' '.$item->status.'.',
            'status' => 'belum_dibaca',
            'warna' => $item->status === 'disetujui' ? 'green' : 'red',
        ]);
    }

    foreach ($kalenderHariIni as $event) {
        if ($liburHariIni && ($event['jenis'] ?? null) === 'libur') {
            continue;
        }

        $notifikasi->push([
            'kategori' => 'kalender',
            'judul' => 'Hari Ini '.ucfirst($event['jenis']),
            'pesan' => $event['judul'].($event['keterangan'] ? ' - '.$event['keterangan'] : ''),
            'status' => 'belum_dibaca',
            'warna' => $event['jenis'] === 'libur' ? 'red' : ($event['jenis'] === 'ujian' ? 'orange' : 'blue'),
        ]);
    }

    $settings = DB::table('attendance_settings')
        ->whereIn('key', ['nama_sekolah', 'logo_sekolah'])
        ->pluck('value', 'key');

    DB::table('notifications')
        ->where('user_id', $user->id)
        ->where('kategori', 'orang_tua')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();

    $notifikasiOrangTua = DB::table('notifications')
        ->where('user_id', $user->id)
        ->where('kategori', 'orang_tua')
        ->latest('id')
        ->limit(10)
        ->get()
        ->map(function ($item) {
            $payload = json_decode($item->payload ?? '[]', true);

            return [
                'id' => $item->id,
                'judul' => $item->judul,
                'pesan' => $item->pesan,
                'kategori' => $item->kategori,
                'status' => $item->status,
                'warna' => 'blue',
                'payload' => is_array($payload) ? $payload : [],
                'created_at' => $item->created_at,
            ];
        });

    $notifikasiMonitoringOrangTua = $notifikasi
        ->filter(fn ($item) => in_array(($item['kategori'] ?? null), ['absensi_harian', 'kalender'], true))
        ->map(function ($item, $index) use ($tanggal) {
            return [
                'id' => 'monitoring-'.$tanggal.'-'.$index,
                'judul' => $item['judul'],
                'pesan' => $item['pesan'],
                'kategori' => 'orang_tua',
                'status' => $item['status'] ?? 'belum_dibaca',
                'warna' => $item['warna'] ?? 'blue',
                'payload' => $item['payload'] ?? [
                    'tipe' => $item['kategori'] ?? 'absensi',
                    'status' => $item['warna'] ?? 'info',
                    'jam' => '-',
                ],
                'created_at' => now()->toDateTimeString(),
            ];
        })
        ->values();

    $notifikasiOrangTua = $notifikasiMonitoringOrangTua
        ->merge($notifikasiOrangTua)
        ->take(10)
        ->values();

    return response()->json([
        'status' => 'success',
        'tanggal' => $tanggal,
        'hari' => ucfirst($hari),
        'batas_absen_masuk' => $labelBatasAbsenMasuk,
        'siswa' => [
            'id' => $user->id,
            'nama' => $user->nama,
            'nis' => $user->nis,
            'username' => $user->username,
            'kelas' => $kelas->nama_kelas ?? '-',
            'jurusan' => $kelas->nama_jurusan ?? '-',
            'wali_kelas' => $kelas->wali_kelas ?? '-',
            'nama_ortu' => $user->nama_ortu ?: '-',
            'no_ortu' => $user->no_ortu ?: '-',
            'status_akun' => $user->aktif ? 'Aktif' : 'Nonaktif',
        ],
        'absensi_hari_ini' => [
            'sudah_masuk' => $absensi && $absensi->jam_masuk ? true : false,
            'jam_masuk' => $absensi->jam_masuk ?? null,
            'status_masuk' => $absensi->status_masuk ?? null,
            'sudah_pulang' => $absensi && $absensi->jam_pulang ? true : false,
            'jam_pulang' => $absensi->jam_pulang ?? null,
            'status_pulang' => $absensi->status_pulang ?? null,
            'status_siswa' => $liburHariIni ? 'libur' : ($absensi->status_masuk ?? $absensi->status_pulang ?? 'belum_absen'),
            'catatan_piket' => $absensi->catatan_piket ?? null,
            'hari_libur' => $liburHariIni ? true : false,
            'keterangan_libur' => $liburHariIni['judul'] ?? null,
        ],
        'scan_control' => [
            'scan_masuk_enabled' => ! $liburHariIni,
            'scan_pulang_enabled' => ! $liburHariIni,
            'scan_mapel_enabled' => ! $liburHariIni,
            'auto_close_seconds' => $liburHariIni ? 5 : null,
            'should_redirect' => $liburHariIni ? true : false,
            'redirect_to' => 'dashboard',
            'title' => $liburHariIni ? 'Hari Ini Libur' : null,
            'message' => $liburHariIni
                ? 'Hari ini libur: '.$liburHariIni['judul'].'. Absensi tidak dibuka dan tidak dihitung alfa.'
                : null,
        ],
        'status_mapel_hari_ini' => [
            'total' => $totalMapel,
            'sudah_absen' => $sudahMapel,
            'belum_absen' => max(0, $totalMapel - $sudahMapel),
            'label' => $totalMapel === 0
                ? 'Tidak ada jadwal mapel hari ini'
                : ($sudahMapel.'/'.$totalMapel.' mapel sudah absen'),
        ],
        'jadwal_hari_ini' => $jadwalHariIni,
        'pengajuan' => $pengajuan,
        'notifikasi' => $notifikasi->values(),
        'notifikasi_orang_tua' => $notifikasiOrangTua,
        'kalender_hari_ini' => $kalenderHariIni,
        'kalender_bulan_ini' => $kalenderMendatang,
        'kartu_pelajar' => [
            'kode' => 'SISWA-'.$user->id.'-'.($user->nis ?: $user->username),
            'nama_sekolah' => $settings['nama_sekolah'] ?? 'Sekolah',
            'logo_url' => url($settings['logo_sekolah'] ?? 'img/logo-ba.png'),
        ],
    ]);
});

Route::get('/siswa/kalender/{siswa_id}', function (Request $request, $siswa_id) {
    $user = User::where('role', 'siswa')->find($siswa_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
    }

    $bulan = (int) ($request->query('bulan') ?: now()->month);
    $tahun = (int) ($request->query('tahun') ?: now()->year);
    $start = \Carbon\Carbon::create($tahun, $bulan, 1)->startOfMonth();
    $end = $start->copy()->endOfMonth();

    return response()->json([
        'status' => 'success',
        'bulan' => $bulan,
        'tahun' => $tahun,
        'events' => apiKalenderSiswa($start->toDateString(), $end->toDateString()),
    ]);
});

Route::get('/mobile/role-context/{user_id}', function ($user_id) {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $hari = strtolower(now()->locale('id')->translatedFormat('l'));
    $isWali = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
    $isGuruMapel = DB::table('jadwal_pelajarans')
        ->whereNull('deleted_at')
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere(function ($pengganti) use ($user) {
                    $pengganti->where('guru_pengganti_id', $user->id)
                        ->where('status_guru', 'digantikan');
                });
        })
        ->exists();
    $isGuruMapelHariIni = DB::table('jadwal_pelajarans')
        ->whereNull('deleted_at')
        ->whereRaw('LOWER(hari) = ?', [$hari])
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere(function ($pengganti) use ($user) {
                    $pengganti->where('guru_pengganti_id', $user->id)
                        ->where('status_guru', 'digantikan');
                });
        })
        ->exists();
    $isGuruPiket = DB::table('guru_pikets')
        ->where('aktif', 1)
        ->whereNull('deleted_at')
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere('guru_pengganti_id', $user->id)
                ->orWhere('guru_pengganti2_id', $user->id);
        })
        ->exists();
    $isGuruPiketHariIni = DB::table('guru_pikets')
        ->where('aktif', 1)
        ->whereNull('deleted_at')
        ->where('hari', $hari)
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere('guru_pengganti_id', $user->id)
                ->orWhere('guru_pengganti2_id', $user->id);
        })
        ->exists();

    $features = collect();
    if ($user->role === 'admin') {
        $features->push(['title' => 'Superadmin', 'subtitle' => 'Kelola seluruh data dan rekap.', 'path' => '/dashboard/admin', 'icon' => 'admin']);
    }
    if ($user->role === 'piket' || $isGuruPiket) {
        $features->push(['title' => 'Guru Piket', 'subtitle' => $isGuruPiketHariIni ? 'Anda bertugas piket hari ini.' : 'Akses jadwal dan rekap piket.', 'path' => '/dashboard/piket', 'icon' => 'piket']);
    }
    if ($user->role === 'guru' && $isGuruMapel) {
        $features->push(['title' => 'Guru Mapel', 'subtitle' => $isGuruMapelHariIni ? 'Ada jadwal mengajar hari ini.' : 'Lihat jadwal, absen mapel, dan rekap.', 'path' => '/dashboard/guru', 'icon' => 'guru']);
    }
    if ($user->role === 'guru' && $isWali) {
        $features->push(['title' => 'Wali Kelas', 'subtitle' => 'Pantau kelas, absensi, dan siswa rawan.', 'path' => '/dashboard/wali', 'icon' => 'wali']);
    }
    if ($user->role === 'siswa') {
        $features->push(['title' => 'Siswa', 'subtitle' => 'Scan QR, izin/sakit, dan riwayat absensi.', 'path' => null, 'icon' => 'siswa']);
    }

    return response()->json([
        'status' => 'success',
        'user' => ['id' => $user->id, 'nama' => $user->nama, 'role' => $user->role],
        'context' => [
            'is_wali' => $isWali,
            'is_guru_mapel' => $isGuruMapel,
            'is_guru_mapel_hari_ini' => $isGuruMapelHariIni,
            'is_guru_piket' => $isGuruPiket,
            'is_guru_piket_hari_ini' => $isGuruPiketHariIni,
        ],
        'features' => $features->values(),
    ]);
});

Route::get('/mobile/piket-dashboard/{user_id}', function ($user_id) {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $hari = strtolower(now()->locale('id')->translatedFormat('l'));
    $tanggal = now()->toDateString();

    $teamBase = DB::table('guru_pikets')
        ->where('hari', $hari)
        ->where('aktif', 1)
        ->whereNull('deleted_at')
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere('guru_pengganti_id', $user->id)
                ->orWhere('guru_pengganti2_id', $user->id);
        })
        ->orderBy('jam_mulai')
        ->first();

    $anggotaTim = collect();
    $penggantiTim = collect();
    $teamKey = null;

    if ($teamBase) {
        $teamKey = implode('|', [
            $teamBase->tahun_ajaran_id ?? 'aktif',
            strtolower((string) $teamBase->hari),
            $teamBase->jam_mulai ?: '-',
            $teamBase->jam_selesai ?: '-',
        ]);

        $anggotaTim = DB::table('guru_pikets as gp')
            ->join('users as u', 'u.id', '=', 'gp.guru_id')
            ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
            ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
            ->where('gp.hari', $teamBase->hari)
            ->where('gp.jam_mulai', $teamBase->jam_mulai)
            ->where('gp.jam_selesai', $teamBase->jam_selesai)
            ->where('gp.aktif', 1)
            ->whereNull('gp.deleted_at')
            ->when($teamBase->tahun_ajaran_id ?? null, fn ($query) => $query->where('gp.tahun_ajaran_id', $teamBase->tahun_ajaran_id))
            ->select('gp.id', 'gp.status', 'u.nama as nama', 'g1.nama as guru_pengganti', 'g2.nama as guru_pengganti2')
            ->orderBy('u.nama')
            ->get();

        $penggantiTim = $anggotaTim
            ->flatMap(fn ($anggota) => [$anggota->guru_pengganti, $anggota->guru_pengganti2])
            ->filter()
            ->unique()
            ->values();
    }

    $qrMasuk = QrCode::whereDate('tanggal', $tanggal)
        ->where('tipe', 'masuk')
        ->when($teamKey && Schema::hasColumn('qr_codes', 'guru_piket_team_key'), fn ($query) => $query->where('guru_piket_team_key', $teamKey))
        ->latest('id')
        ->first();

    $qrPulang = QrCode::whereDate('tanggal', $tanggal)
        ->where('tipe', 'pulang')
        ->when($teamKey && Schema::hasColumn('qr_codes', 'guru_piket_team_key'), fn ($query) => $query->where('guru_piket_team_key', $teamKey))
        ->latest('id')
        ->first();

    $absensiHariIni = DB::table('absensis')
        ->whereDate('tanggal', $tanggal)
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as masuk,
            SUM(CASE WHEN jam_pulang IS NOT NULL THEN 1 ELSE 0 END) as pulang,
            SUM(CASE WHEN status_masuk IN ("izin", "sakit") OR status_pulang IN ("izin", "sakit") THEN 1 ELSE 0 END) as izin_sakit
        ')
        ->first();

    $pengajuanMenunggu = Schema::hasTable('student_permit_requests')
        ? DB::table('student_permit_requests')->where('status', 'menunggu')->whereNull('deleted_at')->count()
        : 0;

    $libur = hariLiburSekolah($tanggal);

    return response()->json([
        'status' => 'success',
        'user' => ['id' => $user->id, 'nama' => $user->nama, 'role' => $user->role],
        'tanggal' => $tanggal,
        'hari' => ucfirst($hari),
        'is_holiday' => (bool) $libur,
        'holiday' => $libur ? ['judul' => $libur->judul, 'keterangan' => $libur->keterangan ?? null] : null,
        'team' => $teamBase ? [
            'key' => $teamKey,
            'hari' => ucfirst($teamBase->hari),
            'jam_mulai' => substr((string) $teamBase->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $teamBase->jam_selesai, 0, 5),
            'anggota' => $anggotaTim->map(fn ($item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'status' => $item->status,
            ])->values(),
            'pengganti' => $penggantiTim,
        ] : null,
        'qr' => [
            'masuk' => $qrMasuk ? [
                'id' => $qrMasuk->id,
                'token' => $qrMasuk->token,
                'expires_at' => optional($qrMasuk->expires_at)->format('H:i'),
            ] : null,
            'pulang' => $qrPulang ? [
                'id' => $qrPulang->id,
                'token' => $qrPulang->token,
                'expires_at' => optional($qrPulang->expires_at)->format('H:i'),
            ] : null,
        ],
        'summary' => [
            'absensi_total' => (int) ($absensiHariIni->total ?? 0),
            'masuk' => (int) ($absensiHariIni->masuk ?? 0),
            'pulang' => (int) ($absensiHariIni->pulang ?? 0),
            'izin_sakit' => (int) ($absensiHariIni->izin_sakit ?? 0),
            'pengajuan_menunggu' => (int) $pengajuanMenunggu,
        ],
    ]);
});

Route::get('/mobile/wali-dashboard/{user_id}', function (Request $request, $user_id) {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $wali = DB::table('kelas as k')
        ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
        ->where('k.wali_kelas_id', $user->id)
        ->select('k.id', 'k.nama_kelas', 'j.nama_jurusan')
        ->first();

    if (! $wali) {
        return response()->json(['status' => 'error', 'message' => 'Akses wali kelas tidak ditemukan'], 403);
    }

    $tanggal = $request->get('tanggal', now()->toDateString());
    $awal30Hari = now()->subDays(30)->toDateString();

    $siswa = DB::table('users as s')
        ->leftJoin('absensis as a', function ($join) use ($tanggal) {
            $join->on('a.id_siswa', '=', 's.id')
                ->whereDate('a.tanggal', $tanggal);
        })
        ->where('s.role', 'siswa')
        ->where('s.kelas_id', $wali->id)
        ->select(
            's.id',
            's.nama',
            's.nis',
            's.username',
            's.nama_ortu',
            's.no_ortu',
            'a.tanggal',
            'a.jam_masuk',
            'a.jam_pulang',
            'a.status_masuk',
            'a.status_pulang'
        )
        ->orderBy('s.nama')
        ->get();

    $absensi = DB::table('absensis as a')
        ->join('users as s', 's.id', '=', 'a.id_siswa')
        ->where('s.kelas_id', $wali->id)
        ->whereDate('a.tanggal', '>=', $awal30Hari)
        ->select(
            'a.id',
            'a.tanggal',
            'a.jam_masuk',
            'a.jam_pulang',
            'a.status_masuk',
            'a.status_pulang',
            's.id as siswa_id',
            's.nama',
            's.nis'
        )
        ->orderByDesc('a.tanggal')
        ->orderBy('s.nama')
        ->limit(300)
        ->get();

    $ringkasan = [
        'total_siswa' => $siswa->count(),
        'hadir_hari_ini' => $siswa->filter(fn ($row) => $row->jam_masuk && ! in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count(),
        'telat_hari_ini' => $siswa->where('status_masuk', 'telat')->count(),
        'belum_absen' => $siswa->filter(fn ($row) => ! $row->jam_masuk && ! in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count(),
        'izin_sakit_hari_ini' => $siswa->filter(fn ($row) => in_array($row->status_masuk, ['izin', 'sakit']) || in_array($row->status_pulang, ['izin', 'sakit']))->count(),
        'alfa_hari_ini' => $siswa->filter(fn ($row) => in_array($row->status_masuk, ['alfa', 'alpa']) || in_array($row->status_pulang, ['alfa', 'alpa']))->count(),
        'hadir_30_hari' => $absensi->filter(fn ($row) => $row->jam_masuk && ! in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count(),
        'telat_30_hari' => $absensi->where('status_masuk', 'telat')->count(),
        'alfa_30_hari' => $absensi->filter(fn ($row) => in_array($row->status_masuk, ['alfa', 'alpa']) || in_array($row->status_pulang, ['alfa', 'alpa']))->count(),
    ];

    $rawan = DB::table('absensis as a')
        ->join('users as s', 's.id', '=', 'a.id_siswa')
        ->where('s.kelas_id', $wali->id)
        ->whereDate('a.tanggal', '>=', $awal30Hari)
        ->where(function ($query) {
            $query->whereIn('a.status_masuk', ['telat', 'izin', 'sakit', 'alfa', 'alpa'])
                ->orWhereIn('a.status_pulang', ['izin', 'sakit', 'alfa', 'alpa', 'pulang_cepat']);
        })
        ->select(
            's.id',
            's.nama',
            's.nis',
            DB::raw('COUNT(*) as total_temuan'),
            DB::raw("SUM(CASE WHEN a.status_masuk = 'telat' THEN 1 ELSE 0 END) as telat"),
            DB::raw("SUM(CASE WHEN a.status_masuk IN ('alfa','alpa') OR a.status_pulang IN ('alfa','alpa') THEN 1 ELSE 0 END) as alfa")
        )
        ->groupBy('s.id', 's.nama', 's.nis')
        ->orderByDesc('total_temuan')
        ->limit(10)
        ->get();

    return response()->json([
        'status' => 'success',
        'tanggal' => $tanggal,
        'wali' => $wali,
        'ringkasan' => $ringkasan,
        'siswa' => $siswa,
        'absensi' => $absensi,
        'rawan' => $rawan,
    ]);
});

Route::get('/mobile/piket-absensi/{user_id}', function (Request $request, $user_id) {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $tanggal = $request->get('tanggal', now()->toDateString());

    $rows = DB::table('users as s')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->leftJoin('absensis as a', function ($join) use ($tanggal) {
            $join->on('a.id_siswa', '=', 's.id')
                ->whereDate('a.tanggal', $tanggal);
        })
        ->where('s.role', 'siswa')
        ->select(
            's.id as siswa_id',
            's.nama',
            's.nis',
            'k.nama_kelas',
            'a.jam_masuk',
            'a.jam_pulang',
            'a.status_masuk',
            'a.status_pulang',
            'a.catatan_piket'
        )
        ->orderBy('k.nama_kelas')
        ->orderBy('s.nama')
        ->limit(300)
        ->get();

    return response()->json([
        'status' => 'success',
        'tanggal' => $tanggal,
        'data' => $rows,
    ]);
});

Route::get('/mobile/piket-riwayat/{user_id}', function (Request $request, $user_id) {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $rows = DB::table('absensis as a')
        ->join('users as s', 's.id', '=', 'a.id_siswa')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->select('a.tanggal', 's.nama', 's.nis', 'k.nama_kelas', 'a.jam_masuk', 'a.jam_pulang', 'a.status_masuk', 'a.status_pulang')
        ->orderByDesc('a.tanggal')
        ->orderBy('k.nama_kelas')
        ->orderBy('s.nama')
        ->limit(120)
        ->get();

    return response()->json(['status' => 'success', 'data' => $rows]);
});

Route::get('/mobile/piket-jadwal/{user_id}', function ($user_id) {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $rows = DB::table('guru_pikets as gp')
        ->join('users as g', 'g.id', '=', 'gp.guru_id')
        ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
        ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
        ->whereNull('gp.deleted_at')
        ->select(
            'gp.id',
            'gp.hari',
            'gp.jam_mulai',
            'gp.jam_selesai',
            'gp.status',
            'gp.aktif',
            'g.nama as guru_utama',
            'g1.nama as guru_pengganti',
            'g2.nama as guru_pengganti2'
        )
        ->orderBy('gp.hari')
        ->orderBy('gp.jam_mulai')
        ->get();

    return response()->json(['status' => 'success', 'data' => $rows]);
});

Route::get('/mobile/piket-pengajuan/{user_id}', function (Request $request, $user_id) {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $tanggal = $request->get('tanggal', now()->toDateString());

    $rows = DB::table('student_permit_requests as p')
        ->join('users as s', 's.id', '=', 'p.siswa_id')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')
        ->whereNull('p.deleted_at')
        ->whereDate('p.tanggal_mulai', '<=', $tanggal)
        ->whereDate('p.tanggal_selesai', '>=', $tanggal)
        ->select('p.*', 's.nama as nama_siswa', 'k.nama_kelas', 'r.nama as reviewer')
        ->latest('p.id')
        ->get();

    return response()->json([
        'status' => 'success',
        'tanggal' => $tanggal,
        'data' => $rows,
    ]);
});

Route::post('/mobile/piket-pengajuan/{id}/review', function (Request $request, $id) {
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'status' => 'required|in:disetujui,ditolak',
        'catatan_review' => 'nullable|string|max:1000',
    ]);

    $pengajuan = DB::table('student_permit_requests')->where('id', $id)->whereNull('deleted_at')->first();
    if (! $pengajuan) {
        return response()->json(['status' => 'error', 'message' => 'Pengajuan tidak ditemukan'], 404);
    }

    $result = prosesReviewPengajuanSiswa((int) $id, $request->status, $request->catatan_review, $request);

    return response()->json([
        'status' => 'success',
        'message' => 'Pengajuan berhasil direview.',
        'result' => $result,
    ]);
});

Route::post('/siswa/pengajuan-izin', function (Request $request) {
    $request->validate([
        'siswa_id' => 'required|exists:users,id',
        'jenis' => 'required|in:izin,sakit',
        'tanggal_mulai' => 'required|date',
        'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        'alasan' => 'nullable|string',
        'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
    ]);

    $path = null;
    if ($request->hasFile('bukti')) {
        $path = $request->file('bukti')->store('bukti-izin', 'public');
    }

    $id = DB::table('student_permit_requests')->insertGetId([
        'siswa_id' => $request->siswa_id,
        'tanggal_mulai' => $request->tanggal_mulai,
        'tanggal_selesai' => $request->tanggal_selesai,
        'jenis' => $request->jenis,
        'alasan' => $request->alasan,
        'bukti_path' => $path,
        'status' => 'menunggu',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json([
        'status' => 'success',
        'message' => 'Pengajuan '.$request->jenis.' berhasil dikirim dan menunggu verifikasi.',
        'id' => $id,
    ]);
});
