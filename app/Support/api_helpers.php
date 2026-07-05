<?php

use App\Models\User;
use App\Services\AttendanceSettingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        $start = Carbon::parse($mulai);
        $end = Carbon::parse($selesai);

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
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}

if (! function_exists('kirimNotifikasiMobile')) {
    function kirimNotifikasiMobile(int $siswaId, string $judul, string $pesan, array $data = [], array $audiences = ['siswa', 'orang_tua']): bool
    {
        $tokens = DB::table('parent_fcm_tokens')
            ->where('siswa_id', $siswaId)
            ->when(Schema::hasColumn('parent_fcm_tokens', 'audience'), fn ($query) => $query->whereIn('audience', $audiences))
            ->pluck('token')->filter()->unique()->values();

        $accessToken = fcmAccessToken();
        $projectId = fcmProjectId();
        if ($tokens->isEmpty() || ! $accessToken || ! $projectId) {
            return false;
        }

        DB::table('notifications')->insert([
            'user_id' => $siswaId, 'judul' => $judul, 'pesan' => $pesan,
            'kategori' => 'mobile_reminder', 'status' => 'belum_dibaca',
            'source_type' => $data['tipe'] ?? 'mobile_reminder',
            'source_id' => $data['jadwal_id'] ?? $siswaId,
            'payload' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $sent = false;
        foreach ($tokens as $token) {
            try {
                $response = Http::withToken($accessToken)->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $judul, 'body' => $pesan],
                        'data' => array_merge([
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'siswa_id' => (string) $siswaId,
                        ], collect($data)->map(fn ($value) => (string) $value)->all()),
                        'android' => [
                            'priority' => 'HIGH',
                            'notification' => ['channel_id' => 'absensi_sekolah', 'sound' => 'default'],
                        ],
                    ],
                ]);
                $sent = $response->successful() || $sent;
                if (! $response->successful()) {
                    Log::warning('FCM pengingat mobile gagal dikirim', ['siswa_id' => $siswaId, 'status' => $response->status()]);
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $sent;
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
            } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            report($e);
        }

        return time();
    }
}

if (! function_exists('apiTrustedDateTime')) {
    function apiTrustedDateTime(): Carbon
    {
        $timestamp = Cache::remember('trusted_server_timestamp', 30, function () {
            try {
                $response = Http::timeout(5)->head('https://www.google.com/generate_204');
                $date = $response->header('Date');
                if ($date && strtotime($date)) {
                    return strtotime($date);
                }
            } catch (Throwable $e) {
                Log::warning('Waktu internet tidak dapat disinkronkan', ['message' => $e->getMessage()]);
            }

            return time();
        });

        // Tambahkan usia cache agar detik tetap mendekati waktu aktual.
        $cachedAt = Cache::get('trusted_server_timestamp_cached_at');
        if (! $cachedAt) {
            Cache::put('trusted_server_timestamp_cached_at', time(), 30);
            $cachedAt = time();
        }

        return Carbon::createFromTimestamp($timestamp + max(0, time() - $cachedAt), 'Asia/Jakarta');
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

        $schoolLat = AttendanceSettingService::latitudeSekolah();
        $schoolLng = AttendanceSettingService::longitudeSekolah();
        $allowedRadius = AttendanceSettingService::radiusAbsensi();
        $earthRadius = 6371000;

        $dLat = deg2rad((float) $lat - $schoolLat);
        $dLng = deg2rad((float) $lng - $schoolLng);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($schoolLat)) * cos(deg2rad((float) $lat)) * sin($dLng / 2) ** 2;
        $distance = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));

        if ($distance > $allowedRadius) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda berada sekitar '.round($distance).' meter dari sekolah. Scan hanya bisa dilakukan dalam radius '.$allowedRadius.' meter dari '.AttendanceSettingService::namaSekolah().'.',
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
