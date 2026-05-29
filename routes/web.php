<?php

use App\Http\Controllers\Admin\AdminFeatureController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Web\AuthWebController;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect('/login');
});

if (! function_exists('jamBentrok')) {
    function jamBentrok($query, string $jamMulai, string $jamSelesai)
    {
        return $query
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai);
    }
}

if (! function_exists('tahunAjaranAktifId')) {
    function tahunAjaranAktifId()
    {
        return DB::table('tahun_ajarans')->where('aktif', true)->value('id');
    }
}

if (! function_exists('wajibSuperadmin')) {
    function wajibSuperadmin(): void
    {
        $user = session('user');
        $level = $user ? DB::table('users')->where('id', $user->id)->value('admin_level') : null;

        abort_if(! $user || $user->role !== 'admin' || $level !== 'superadmin', 403, 'Hanya superadmin yang boleh mengakses fitur ini.');
    }
}

if (! function_exists('buatNotifikasi')) {
    function buatNotifikasi(array $data): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $payload = [
            'user_id' => $data['user_id'] ?? null,
            'judul' => $data['judul'] ?? 'Notifikasi',
            'pesan' => $data['pesan'] ?? null,
            'status' => $data['status'] ?? 'belum_dibaca',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach (['kategori', 'severity', 'source_type', 'source_id', 'payload'] as $column) {
            if (Schema::hasColumn('notifications', $column)) {
                $payload[$column] = $column === 'payload'
                    ? json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE)
                    : ($data[$column] ?? ($column === 'severity' ? 'info' : null));
            }
        }

        DB::table('notifications')->insert($payload);
    }
}

if (! function_exists('hariLiburSekolah')) {
    function hariLiburSekolah($tanggal)
    {
        if (! Schema::hasTable('kalender_sekolahs')) {
            return null;
        }

        $hari = strtolower(\Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l'));

        return DB::table('kalender_sekolahs')
            ->where('jenis', 'libur')
            ->where(function ($query) use ($tanggal, $hari) {
                $query->where(function ($date) use ($tanggal) {
                    $date->whereDate('tanggal_mulai', '<=', $tanggal)
                        ->whereDate('tanggal_selesai', '>=', $tanggal);
                })->orWhere(function ($repeat) use ($hari) {
                    $repeat->where('berulang', 1)->where('hari_berulang', $hari);
                });
            })
            ->first();
    }
}

if (! function_exists('kalenderSekolahTanggal')) {
    function kalenderSekolahTanggal($tanggal)
    {
        if (! Schema::hasTable('kalender_sekolahs')) {
            return collect();
        }

        $hari = strtolower(\Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l'));

        return DB::table('kalender_sekolahs')
            ->where(function ($query) use ($tanggal, $hari) {
                $query->where(function ($date) use ($tanggal) {
                    $date->whereDate('tanggal_mulai', '<=', $tanggal)
                        ->whereDate('tanggal_selesai', '>=', $tanggal);
                })->orWhere(function ($repeat) use ($hari) {
                    $repeat->where('berulang', 1)->where('hari_berulang', $hari);
                });
            })
            ->orderByRaw("FIELD(jenis, 'libur', 'ujian', 'kegiatan')")
            ->get();
    }
}

if (! function_exists('buatNotifikasiKalenderBesok')) {
    function buatNotifikasiKalenderBesok(): void
    {
        if (! Schema::hasTable('kalender_sekolahs') || ! Schema::hasTable('notifications')) {
            return;
        }

        $besok = now()->addDay()->toDateString();
        foreach (kalenderSekolahTanggal($besok) as $event) {
            $pesan = 'Besok ada '.ucfirst($event->jenis).': '.$event->judul.' ('.$besok.').';
            $exists = DB::table('notifications')
                ->where('judul', 'Pengingat Kalender Sekolah')
                ->where('pesan', $pesan)
                ->exists();

            if (! $exists) {
                buatNotifikasi([
                    'user_id' => null,
                    'judul' => 'Pengingat Kalender Sekolah',
                    'pesan' => $pesan,
                    'kategori' => 'kalender',
                    'severity' => $event->jenis === 'libur' ? 'danger' : 'info',
                    'source_type' => 'kalender_sekolahs',
                    'source_id' => $event->id ?? null,
                    'payload' => [
                        'jenis' => $event->jenis,
                        'tanggal' => $besok,
                        'judul_event' => $event->judul,
                    ],
                    'status' => 'belum_dibaca',
                ]);
            }
        }
    }
}

if (! function_exists('daftarProvinsiIndonesia')) {
    function daftarProvinsiIndonesia(): array
    {
        return [
            'Nasional', 'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau',
            'Jambi', 'Bengkulu', 'Sumatera Selatan', 'Bangka Belitung', 'Lampung', 'Banten',
            'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur', 'Bali',
            'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat', 'Kalimantan Tengah',
            'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara', 'Sulawesi Utara',
            'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan',
            'Sulawesi Tenggara', 'Maluku', 'Maluku Utara', 'Papua', 'Papua Barat',
        ];
    }
}

if (! function_exists('validasiBentrokJadwalPelajaran')) {
    function validasiBentrokJadwalPelajaran(Request $request, ?int $ignoreId = null): ?string
    {
        if ($request->jam_mulai >= $request->jam_selesai) {
            return 'Jam selesai harus lebih besar dari jam mulai.';
        }

        $hari = strtolower($request->hari);
        $tahunAjaranId = $request->tahun_ajaran_id ?: tahunAjaranAktifId();

        $kelasBentrok = DB::table('jadwal_pelajarans')
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where('kelas_id', $request->kelas_id);

        if ($bentrok = jamBentrok($kelasBentrok, $request->jam_mulai, $request->jam_selesai)->first()) {
            $kelas = DB::table('kelas')->where('id', $request->kelas_id)->value('nama_kelas') ?: 'Kelas';
            return $kelas.' sudah memiliki jadwal pelajaran pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        $guruIds = array_values(array_filter([
            $request->guru_id,
            $request->guru_pengganti_id,
        ]));

        $guruBentrok = DB::table('jadwal_pelajarans')
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where(function ($query) use ($guruIds) {
                $query->whereIn('guru_id', $guruIds)
                    ->orWhereIn('guru_pengganti_id', $guruIds);
            });

        if ($guruIds && ($bentrok = jamBentrok($guruBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            $namaGuru = User::whereIn('id', $guruIds)->where(function ($query) use ($bentrok) {
                $query->where('id', $bentrok->guru_id)->orWhere('id', $bentrok->guru_pengganti_id);
            })->value('nama') ?: 'Guru tersebut';
            return $namaGuru.' sudah memiliki jadwal mengajar pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        $guruPiketBentrok = DB::table('guru_pikets')
            ->when($tahunAjaranId && Schema::hasColumn('guru_pikets', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where('aktif', 1)
            ->where(function ($query) use ($guruIds) {
                $query->whereIn('guru_id', $guruIds)
                    ->orWhereIn('guru_pengganti_id', $guruIds)
                    ->orWhereIn('guru_pengganti2_id', $guruIds);
            });

        if ($guruIds && ($bentrok = jamBentrok($guruPiketBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            $namaGuru = User::whereIn('id', $guruIds)->where(function ($query) use ($bentrok) {
                $query->where('id', $bentrok->guru_id)
                    ->orWhere('id', $bentrok->guru_pengganti_id)
                    ->orWhere('id', $bentrok->guru_pengganti2_id);
            })->value('nama') ?: 'Guru tersebut';
            return $namaGuru.' sedang bertugas sebagai guru piket pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        return null;
    }
}

if (! function_exists('validasiJadwalSaatLibur')) {
    function validasiJadwalSaatLibur(Request $request): ?string
    {
        if (! Schema::hasTable('kalender_sekolahs')) {
            return null;
        }

        $tahunAjaranId = $request->tahun_ajaran_id ?: tahunAjaranAktifId();
        $hari = strtolower($request->hari);
        $hariIni = strtolower(now()->locale('id')->translatedFormat('l'));
        $liburHariIni = hariLiburSekolah(now()->toDateString());

        if ($liburHariIni && $hari === $hariIni) {
            return 'Jadwal ditolak. Hari ini libur: '.$liburHariIni->judul.'. Tidak bisa input mapel atau membuat jadwal pada hari libur.';
        }

        $berulang = DB::table('kalender_sekolahs')
            ->where('jenis', 'libur')
            ->where('berulang', 1)
            ->where('hari_berulang', $hari)
            ->where(function ($query) use ($tahunAjaranId) {
                $query->where('tahun_ajaran_id', $tahunAjaranId)->orWhereNull('tahun_ajaran_id');
            })
            ->first();

        if ($berulang) {
            return 'Tidak bisa membuat jadwal mapel pada hari '.ucfirst($hari).', karena ditandai libur: '.$berulang->judul.'.';
        }

        return null;
    }
}

if (! function_exists('validasiBentrokGuruPiket')) {
    function validasiBentrokGuruPiket(Request $request, ?int $ignoreId = null): ?string
    {
        if ($request->jam_mulai >= $request->jam_selesai) {
            return 'Jam selesai harus lebih besar dari jam mulai.';
        }

        $hari = strtolower($request->hari);
        $tahunAjaranId = $request->tahun_ajaran_id ?: tahunAjaranAktifId();
        $guruIds = array_values(array_unique(array_filter(array_merge(
            (array) $request->guru_id,
            [
                $request->guru_pengganti_id,
                $request->guru_pengganti2_id,
            ]
        ))));

        $piketBentrok = DB::table('guru_pikets')
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->when($tahunAjaranId && Schema::hasColumn('guru_pikets', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where('aktif', 1)
            ->where(function ($query) use ($guruIds) {
                $query->whereIn('guru_id', $guruIds)
                    ->orWhereIn('guru_pengganti_id', $guruIds)
                    ->orWhereIn('guru_pengganti2_id', $guruIds);
            });

        if ($guruIds && ($bentrok = jamBentrok($piketBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            return 'Ada guru yang sudah memiliki jadwal piket pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        $jadwalBentrok = DB::table('jadwal_pelajarans')
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where(function ($query) use ($guruIds) {
                $query->whereIn('guru_id', $guruIds)
                    ->orWhereIn('guru_pengganti_id', $guruIds);
            });

        if ($guruIds && ($bentrok = jamBentrok($jadwalBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            return 'Ada guru yang sudah memiliki jadwal mengajar pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.', jadi tidak bisa dijadikan guru piket.';
        }

        return null;
    }
}

if (! function_exists('detailProfilSiswaData')) {
    function detailProfilSiswaData(int $siswaId): array
    {
        $siswa = DB::table('users as s')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')
            ->where('s.id', $siswaId)
            ->where('s.role', 'siswa')
            ->select('s.*', 'k.nama_kelas', 'j.kode_jurusan', 'w.nama as nama_wali')
            ->first();

        abort_if(! $siswa, 404);

        $absensiHarian = DB::table('absensis')
            ->where('id_siswa', $siswaId)
            ->orderByDesc('tanggal')
            ->limit(60)
            ->get();

        $absensiMapel = DB::table('absensi_mapels as a')
            ->join('jadwal_pelajarans as jp', 'jp.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->where('a.siswa_id', $siswaId)
            ->select('a.*', 'm.nama_mapel', 'g.nama as nama_guru')
            ->orderByDesc('a.tanggal')
            ->limit(80)
            ->get();

        $defaultAlfa = AttendanceSettingService::statusDefaultAlfa();

        $ringkasanHarian = [
            'hadir' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
            $defaultAlfa => 0,
        ];
        $riwayatKhusus = collect();

        foreach ($absensiHarian as $row) {
            $status = in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa'])
                ? $row->status_masuk
                : (in_array($row->status_pulang, ['izin', 'sakit', 'alfa', 'alpa']) ? $row->status_pulang : null);

            if (! $status) {
                $status = in_array($row->status_masuk, ['telat', 'terlambat']) ? 'telat' : 'hadir';
            }

            $key = in_array($status, ['alfa', 'alpa']) ? $defaultAlfa : $status;
            $ringkasanHarian[$key] = ($ringkasanHarian[$key] ?? 0) + 1;

            if (in_array($key, ['izin', 'sakit', 'alfa', 'alpa'])) {
                $riwayatKhusus->push([
                    'tanggal' => $row->tanggal,
                    'sumber' => 'Absensi Harian',
                    'status' => $key,
                    'keterangan' => 'Masuk: '.($row->status_masuk ?? '-').' | Pulang: '.($row->status_pulang ?? '-'),
                ]);
            }
        }

        $ringkasanMapel = [
            'hadir' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
            $defaultAlfa => 0,
        ];

        foreach ($absensiMapel as $row) {
            $key = strtolower($row->status ?? 'hadir');
            $key = in_array($key, ['alfa', 'alpa']) ? $defaultAlfa : $key;
            $ringkasanMapel[$key] = ($ringkasanMapel[$key] ?? 0) + 1;

            if (in_array($key, ['izin', 'sakit', 'alfa', 'alpa'])) {
                $riwayatKhusus->push([
                    'tanggal' => $row->tanggal,
                    'sumber' => 'Absensi Mapel',
                    'status' => $key,
                    'keterangan' => ($row->nama_mapel ?? '-').' | Guru: '.($row->nama_guru ?? '-'),
                ]);
            }
        }

        return [
            'siswa' => $siswa,
            'absensiHarian' => $absensiHarian,
            'absensiMapel' => $absensiMapel,
            'ringkasanHarian' => $ringkasanHarian,
            'ringkasanMapel' => $ringkasanMapel,
            'riwayatKhusus' => $riwayatKhusus->sortByDesc('tanggal')->values(),
        ];
    }
}

/*
|--------------------------------------------------------------------------
| LOGIN WEB
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::get('/logout', [AuthWebController::class, 'logout']);

Route::get('/heartbeat', function (Request $request) {
    $user = session('user');

    if (! $user) {
        return response()->json(['ok' => false], 401);
    }

    DB::table('user_login_statuses')->updateOrInsert(
        ['user_id' => $user->id],
        [
            'role' => $user->role,
            'is_online' => true,
            'last_seen_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'updated_at' => now(),
            'created_at' => now(),
        ]
    );

    return response()->json(['ok' => true]);
});

/*
|--------------------------------------------------------------------------
| DASHBOARD ADMIN
|--------------------------------------------------------------------------
*/
Route::get(

    '/dashboard/admin',

    function () {

        $user =
        session(
            'user'
        );

        $totalSiswa =

        User::where(

            'role',

            'siswa'

        )
            ->count();

        $totalGuru =

        User::where(

            'role',

            'guru'

        )
            ->count();

        $totalKelas =

        DB::table(

            'kelas'

        )
            ->count();

        $totalJurusan =

        DB::table(

            'jurusan'

        )
            ->count();

        $totalNotifikasi = DB::table('notifications')
            ->whereNull('user_id')
            ->where('status', 'belum_dibaca')
            ->count();
        buatNotifikasiKalenderBesok();

        $tahunAjaranAktif = DB::table('tahun_ajarans')
            ->where('aktif', true)
            ->first();
        $kalenderHariIni = kalenderSekolahTanggal(now()->toDateString());

        $absensiHariIni = DB::table('absensis')
            ->whereDate('tanggal', now()->toDateString());

        $totalMasukHariIni = (clone $absensiHariIni)
            ->whereNotNull('jam_masuk')
            ->count();

        $totalPulangHariIni = (clone $absensiHariIni)
            ->whereNotNull('jam_pulang')
            ->count();

        $totalBelumAbsen = max($totalSiswa - $totalMasukHariIni, 0);

        $siswaPerKelas = DB::table('kelas as k')
            ->leftJoin('users as s', function ($join) {
                $join->on('s.kelas_id', '=', 'k.id')
                    ->where('s.role', 'siswa');
            })
            ->select('k.nama_kelas', DB::raw('COUNT(s.id) as total'))
            ->groupBy('k.id', 'k.nama_kelas')
            ->orderBy('k.nama_kelas')
            ->get();

        $guruPiketAktif = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->count();

        $guruPiketTidakHadir = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereIn('status', ['Izin', 'Sakit'])
            ->count();

        $chartData = [
            'absensi' => [
                'labels' => ['Masuk', 'Pulang', 'Belum Absen'],
                'values' => [$totalMasukHariIni, $totalPulangHariIni, $totalBelumAbsen],
            ],
            'kelas' => [
                'labels' => $siswaPerKelas->pluck('nama_kelas')->values(),
                'values' => $siswaPerKelas->pluck('total')->values(),
            ],
            'piket' => [
                'labels' => ['Bertugas', 'Tidak Hadir'],
                'values' => [$guruPiketAktif, $guruPiketTidakHadir],
            ],
        ];

        return view(

            'dashboard.admin',

            compact(

                'user',

                'totalSiswa',

                'totalGuru',

                'totalKelas',

                'totalJurusan',

                'totalNotifikasi',
                'tahunAjaranAktif',
                'kalenderHariIni',

                'totalMasukHariIni',

                'totalPulangHariIni',

                'totalBelumAbsen',

                'guruPiketAktif',

                'guruPiketTidakHadir',

                'chartData'

            )

        );

    })
    ->middleware(

        'webrole:admin'

    )
    ->name('dashboard.admin');

Route::get('/dashboard/admin/online-users', function () {
    $offlineLimit = now()->subMinutes(2);

    DB::table('user_login_statuses')
        ->where('is_online', true)
        ->where('last_seen_at', '<', $offlineLimit)
        ->update([
            'is_online' => false,
            'logout_at' => now(),
            'updated_at' => now(),
        ]);

    $users = DB::table('user_login_statuses as s')
        ->join('users as u', 'u.id', '=', 's.user_id')
        ->leftJoin('kelas as k', 'k.id', '=', 'u.kelas_id')
        ->where('s.is_online', true)
        ->where('s.last_seen_at', '>=', $offlineLimit)
        ->select(
            'u.id',
            'u.nama',
            'u.username',
            'u.role',
            'k.nama_kelas',
            's.login_at',
            's.last_seen_at',
            's.ip_address'
        )
        ->orderByDesc('s.last_seen_at')
        ->get()
        ->map(function ($row) {
            return [
                'id' => $row->id,
                'nama' => $row->nama,
                'username' => $row->username,
                'role' => ucfirst($row->role),
                'kelas' => $row->nama_kelas ?: '-',
                'login_at' => optional($row->login_at ? \Carbon\Carbon::parse($row->login_at) : null)->format('H:i:s') ?: '-',
                'last_seen_at' => optional($row->last_seen_at ? \Carbon\Carbon::parse($row->last_seen_at) : null)->diffForHumans() ?: '-',
                'ip_address' => $row->ip_address ?: '-',
            ];
        });

    return response()->json([
        'total' => $users->count(),
        'users' => $users,
        'checked_at' => now()->format('H:i:s'),
    ]);
})->middleware('webrole:admin');

Route::get('/dashboard/admin/notifikasi', function () {

    $user = session('user');
    $kategoriAktif = request('kategori', 'semua');
    $labelKategori = [
        'semua' => 'Semua',
        'absensi_siswa_diubah' => 'Absensi siswa diubah',
        'guru_tidak_hadir' => 'Guru tidak hadir',
        'jadwal_digantikan' => 'Jadwal digantikan',
        'guru_piket_pengganti' => 'Guru piket pengganti',
        'login_mencurigakan' => 'Login mencurigakan',
    ];

    $jadwalDigantikan = DB::table('jadwal_pelajarans as j')
        ->join('users as g', 'g.id', '=', 'j.guru_id')
        ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
        ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
        ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
        ->where('j.status_guru', 'digantikan')
        ->select(
            'j.id',
            'j.hari',
            'j.jam_mulai',
            'j.jam_selesai',
            'j.alasan_tidak_hadir',
            'j.updated_at',
            'g.nama as guru_utama',
            'gp.nama as guru_pengganti',
            'k.nama_kelas',
            'm.nama_mapel'
        )
        ->latest('j.updated_at')
        ->get()
        ->map(function ($item) {
            return (object) [
                'kategori' => 'jadwal_digantikan',
                'tipe' => 'Jadwal digantikan',
                'severity' => 'info',
                'judul' => 'Guru '.$item->guru_utama.' '.($item->alasan_tidak_hadir ?? 'tidak hadir'),
                'utama' => $item->guru_utama,
                'pengganti' => $item->guru_pengganti ?? '-',
                'alasan' => $item->alasan_tidak_hadir ?? '-',
                'detail' => $item->nama_mapel.' | '.$item->nama_kelas,
                'waktu' => $item->hari.', '.$item->jam_mulai.' - '.$item->jam_selesai,
                'created_at' => $item->updated_at,
            ];
        });

    $guruPiketDigantikan = DB::table('guru_pikets as gp')
        ->join('users as g', 'g.id', '=', 'gp.guru_id')
        ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
        ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
        ->whereIn('gp.status', ['Izin', 'Sakit'])
        ->select(
            'gp.*',
            'g.nama as guru_utama',
            'g1.nama as guru_pengganti',
            'g2.nama as guru_pengganti2'
        )
        ->latest('gp.updated_at')
        ->get()
        ->map(function ($item) {
            $pengganti = collect([
                $item->guru_pengganti,
                $item->guru_pengganti2,
            ])->filter()->implode(', ');

            return (object) [
                'kategori' => 'guru_piket_pengganti',
                'tipe' => 'Guru piket pengganti',
                'severity' => 'warning',
                'judul' => 'Guru piket '.$item->guru_utama.' '.$item->status,
                'utama' => $item->guru_utama,
                'pengganti' => $pengganti ?: '-',
                'alasan' => $item->status,
                'detail' => 'Tugas guru piket',
                'waktu' => ucfirst($item->hari).', '.$item->jam_mulai.' - '.$item->jam_selesai,
                'created_at' => $item->updated_at,
            ];
        });

    $notifikasiManual = DB::table('notifications')
        ->whereNull('user_id')
        ->latest('id')
        ->limit(80)
        ->get()
        ->map(function ($item) {
            $payload = [];
            if (! empty($item->payload)) {
                $payload = json_decode($item->payload, true) ?: [];
            }

            $kategori = $item->kategori ?? null;
            if ($item->judul === 'Absensi Siswa Diubah Guru Mapel') {
                preg_match(
                    '/^(.*?) mengubah absensi (.*?) kelas (.*?) tanggal (.*?)\. Status masuk: (.*?), status pulang: (.*?)\.$/',
                    $item->pesan ?? '',
                    $matches
                );

                return (object) [
                    'kategori' => 'absensi_siswa_diubah',
                    'tipe' => 'Absensi siswa diubah',
                    'severity' => $item->severity ?? 'info',
                    'judul' => $item->judul,
                    'utama' => $payload['guru_mapel'] ?? ($matches[1] ?? '-'),
                    'pengganti' => $payload['siswa'] ?? ($matches[2] ?? '-'),
                    'alasan' => 'Masuk: '.($payload['status_masuk'] ?? ($matches[5] ?? '-')).' | Pulang: '.($payload['status_pulang'] ?? ($matches[6] ?? '-')),
                    'detail' => 'Kelas: '.($payload['kelas'] ?? ($matches[3] ?? '-')),
                    'waktu' => $payload['tanggal'] ?? ($matches[4] ?? '-'),
                    'label_utama' => 'Guru mapel',
                    'label_pengganti' => 'Siswa',
                    'label_alasan' => 'Status',
                    'created_at' => $item->created_at,
                ];
            }

            if ($kategori === 'guru_tidak_hadir') {
                return (object) [
                    'kategori' => 'guru_tidak_hadir',
                    'tipe' => 'Guru tidak hadir',
                    'severity' => $item->severity ?? 'warning',
                    'judul' => $item->judul,
                    'utama' => $payload['guru_utama'] ?? '-',
                    'pengganti' => $payload['guru_pengganti'] ?? '-',
                    'alasan' => $payload['alasan'] ?? '-',
                    'detail' => 'Kelas: '.($payload['kelas'] ?? '-'),
                    'waktu' => $payload['jam'] ?? '-',
                    'created_at' => $item->created_at,
                ];
            }

            if ($kategori === 'jadwal_digantikan') {
                return (object) [
                    'kategori' => 'jadwal_digantikan',
                    'tipe' => 'Jadwal digantikan',
                    'severity' => $item->severity ?? 'info',
                    'judul' => $item->judul,
                    'utama' => $payload['guru_utama'] ?? '-',
                    'pengganti' => '-',
                    'alasan' => 'Pengganti mapel',
                    'detail' => 'Kelas: '.($payload['kelas'] ?? '-'),
                    'waktu' => $payload['jam'] ?? '-',
                    'label_pengganti' => 'Info',
                    'created_at' => $item->created_at,
                ];
            }

            if ($kategori === 'guru_piket_pengganti') {
                return (object) [
                    'kategori' => 'guru_piket_pengganti',
                    'tipe' => 'Guru piket pengganti',
                    'severity' => $item->severity ?? 'warning',
                    'judul' => $item->judul,
                    'utama' => $payload['guru_utama'] ?? '-',
                    'pengganti' => $payload['guru_pengganti'] ?? '-',
                    'alasan' => $payload['alasan'] ?? '-',
                    'detail' => 'Jadwal dialihkan: '.($payload['jadwal_dialihkan'] ?? '-'),
                    'waktu' => ($payload['hari'] ?? '-').', '.($payload['jam'] ?? '-'),
                    'created_at' => $item->created_at,
                ];
            }

            if ($kategori === 'login_mencurigakan') {
                return (object) [
                    'kategori' => 'login_mencurigakan',
                    'tipe' => 'Login mencurigakan',
                    'severity' => 'danger',
                    'judul' => $item->judul,
                    'utama' => $payload['username'] ?? '-',
                    'pengganti' => $payload['ip_address'] ?? '-',
                    'alasan' => ($payload['total_gagal'] ?? '-').' percobaan gagal',
                    'detail' => $item->pesan,
                    'waktu' => $payload['waktu'] ?? '-',
                    'label_utama' => 'Username',
                    'label_pengganti' => 'IP',
                    'label_alasan' => 'Percobaan',
                    'created_at' => $item->created_at,
                ];
            }

            return (object) [
                'kategori' => $kategori ?: 'sistem',
                'tipe' => 'Sistem',
                'severity' => $item->severity ?? 'info',
                'judul' => $item->judul ?? 'Notifikasi',
                'utama' => '-',
                'pengganti' => '-',
                'alasan' => '-',
                'detail' => $item->pesan,
                'waktu' => '-',
                'label_utama' => 'Guru utama',
                'label_pengganti' => 'Guru pengganti',
                'label_alasan' => 'Alasan',
                'created_at' => $item->created_at,
            ];
        });

    $notifikasi = $jadwalDigantikan
        ->merge($guruPiketDigantikan)
        ->merge($notifikasiManual)
        ->sortByDesc('created_at')
        ->values();

    $ringkasan = collect($labelKategori)
        ->except('semua')
        ->mapWithKeys(fn ($label, $key) => [$key => $notifikasi->where('kategori', $key)->count()]);

    if ($kategoriAktif !== 'semua') {
        $notifikasi = $notifikasi->where('kategori', $kategoriAktif)->values();
    }

    DB::table('notifications')
        ->whereNull('user_id')
        ->where('status', 'belum_dibaca')
        ->update([
            'status' => 'dibaca',
            'updated_at' => now(),
        ]);

    return view('dashboard.notifikasi', compact('user', 'notifikasi', 'labelKategori', 'kategoriAktif', 'ringkasan'));

})->middleware('webrole:admin');

Route::middleware('webrole:admin')->group(function () {
    Route::get('/dashboard/admin/kalender-sekolah', function (Request $request) {
        $user = session('user');
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $provinsi = $request->get('provinsi', 'Banten');
        $bulan = $request->get('bulan', now()->format('Y-m'));
        $monthStart = \Carbon\Carbon::parse($bulan.'-01')->startOfMonth();
        $monthEnd = (clone $monthStart)->endOfMonth();

        $kalender = DB::table('kalender_sekolahs')
            ->when($tahunAjaranId, fn ($query) => $query->where(function ($where) use ($tahunAjaranId) {
                $where->where('tahun_ajaran_id', $tahunAjaranId)->orWhereNull('tahun_ajaran_id');
            }))
            ->when($provinsi, fn ($query) => $query->where(function ($where) use ($provinsi) {
                $where->where('provinsi', $provinsi)->orWhere('provinsi', 'Nasional')->orWhereNull('provinsi');
            }))
            ->orderByDesc('tanggal_mulai')
            ->get();

        $kalenderBulan = $kalender
            ->filter(fn ($item) => $item->tanggal_mulai <= $monthEnd->toDateString() && $item->tanggal_selesai >= $monthStart->toDateString())
            ->values();
        $provinsiList = daftarProvinsiIndonesia();

        return view('dashboard.kalender_sekolah.index', compact('user', 'kalender', 'tahunAjaran', 'tahunAjaranId', 'provinsi', 'provinsiList', 'bulan', 'monthStart', 'kalenderBulan'));
    });

    Route::get('/dashboard/admin/kalender-sekolah/create', function () {
        $user = session('user');
        $mode = 'create';
        $kalender = null;
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $provinsiList = daftarProvinsiIndonesia();
        $defaultProvinsi = 'Banten';

        return view('dashboard.kalender_sekolah.form', compact('user', 'mode', 'kalender', 'tahunAjaran', 'provinsiList', 'defaultProvinsi'));
    });

    Route::post('/dashboard/admin/kalender-sekolah/store', function (Request $request) {
        $request->validate([
            'judul' => 'required|string|max:150',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'jenis' => 'required|in:libur,kegiatan,ujian',
            'provinsi' => 'nullable|string|max:80',
            'hari_berulang' => 'nullable|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string',
        ]);

        $newId = DB::table('kalender_sekolahs')->insertGetId([
            'tahun_ajaran_id' => $request->tahun_ajaran_id ?: null,
            'judul' => $request->judul,
            'jenis' => $request->jenis,
            'provinsi' => $request->provinsi ?: null,
            'sumber' => 'manual',
            'berulang' => $request->has('berulang') ? 1 : 0,
            'hari_berulang' => $request->has('berulang') ? $request->hari_berulang : null,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'keterangan' => $request->keterangan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLogger::record('create', 'kalender_sekolahs', (int) $newId, 'Kalender sekolah ditambahkan', null, DB::table('kalender_sekolahs')->where('id', $newId)->first(), $request);

        return redirect('/dashboard/admin/kalender-sekolah')->with('success', 'Kalender sekolah berhasil ditambahkan.');
    });

    Route::get('/dashboard/admin/kalender-sekolah/edit/{id}', function ($id) {
        $user = session('user');
        $mode = 'edit';
        $kalender = DB::table('kalender_sekolahs')->where('id', $id)->first();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $provinsiList = daftarProvinsiIndonesia();
        $defaultProvinsi = 'Banten';

        abort_if(! $kalender, 404);

        return view('dashboard.kalender_sekolah.form', compact('user', 'mode', 'kalender', 'tahunAjaran', 'provinsiList', 'defaultProvinsi'));
    });

    Route::post('/dashboard/admin/kalender-sekolah/update/{id}', function (Request $request, $id) {
        $request->validate([
            'judul' => 'required|string|max:150',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'jenis' => 'required|in:libur,kegiatan,ujian',
            'provinsi' => 'nullable|string|max:80',
            'hari_berulang' => 'nullable|in:senin,selasa,rabu,kamis,jumat,sabtu,minggu',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan' => 'nullable|string',
        ]);

        $before = DB::table('kalender_sekolahs')->where('id', $id)->first();
        abort_if(! $before, 404);

        DB::table('kalender_sekolahs')->where('id', $id)->update([
            'tahun_ajaran_id' => $request->tahun_ajaran_id ?: null,
            'judul' => $request->judul,
            'jenis' => $request->jenis,
            'provinsi' => $request->provinsi ?: null,
            'berulang' => $request->has('berulang') ? 1 : 0,
            'hari_berulang' => $request->has('berulang') ? $request->hari_berulang : null,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'keterangan' => $request->keterangan,
            'updated_at' => now(),
        ]);

        AuditLogger::record('update', 'kalender_sekolahs', (int) $id, 'Kalender sekolah diupdate', $before, DB::table('kalender_sekolahs')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/kalender-sekolah')->with('success', 'Kalender sekolah berhasil diupdate.');
    });

    Route::get('/dashboard/admin/kalender-sekolah/delete/{id}', function ($id) {
        $before = DB::table('kalender_sekolahs')->where('id', $id)->first();
        abort_if(! $before, 404);

        DB::table('kalender_sekolahs')->where('id', $id)->delete();
        AuditLogger::record('delete', 'kalender_sekolahs', (int) $id, 'Kalender sekolah dihapus', $before, null, request());

        return back()->with('success', 'Kalender sekolah berhasil dihapus.');
    });

    Route::post('/dashboard/admin/kalender-sekolah/auto-nasional', function (Request $request) {
        $request->validate([
            'tahun' => 'required|integer|min:2020|max:2100',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'provinsi' => 'nullable|string|max:80',
        ]);

        $response = Http::timeout(12)->get('https://date.nager.at/api/v3/PublicHolidays/'.$request->tahun.'/ID');

        if (! $response->successful()) {
            return back()->with('error', 'Gagal mengambil tanggal merah nasional. Coba lagi nanti atau import Excel.');
        }

        $created = 0;

        foreach ($response->json() as $holiday) {
            $tanggal = $holiday['date'] ?? null;
            $judul = $holiday['localName'] ?? $holiday['name'] ?? 'Tanggal Merah Nasional';

            if (! $tanggal) {
                continue;
            }

            $exists = DB::table('kalender_sekolahs')
                ->whereDate('tanggal_mulai', $tanggal)
                ->whereDate('tanggal_selesai', $tanggal)
                ->where('judul', $judul)
                ->where('sumber', 'nasional')
                ->exists();

            if ($exists) {
                continue;
            }

            $newId = DB::table('kalender_sekolahs')->insertGetId([
                'tahun_ajaran_id' => $request->tahun_ajaran_id ?: null,
                'tanggal_mulai' => $tanggal,
                'tanggal_selesai' => $tanggal,
                'judul' => $judul,
                'jenis' => 'libur',
                'provinsi' => $request->provinsi ?: 'Nasional',
                'sumber' => 'nasional',
                'keterangan' => 'Tanggal merah nasional otomatis',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AuditLogger::record('create', 'kalender_sekolahs', (int) $newId, 'Tanggal merah nasional otomatis', null, DB::table('kalender_sekolahs')->where('id', $newId)->first(), $request);
            $created++;
        }

        return back()->with('success', 'Tanggal merah nasional berhasil diisi: '.$created.' data baru.');
    });

    Route::get('/dashboard/admin/pengaturan', function () {
        $user = session('user');
        $settings = AttendanceSettingService::all();

        return view('dashboard.pengaturan', compact('user', 'settings'));
    });

    Route::post('/dashboard/admin/pengaturan', function (Request $request) {
        $request->validate([
            'nama_sekolah' => 'required|string|max:120',
            'logo_sekolah' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'jam_masuk' => 'required|date_format:H:i',
            'batas_telat' => 'required|date_format:H:i|after_or_equal:jam_masuk',
            'jam_pulang' => 'required|date_format:H:i|after:batas_telat',
            'masa_aktif_qr' => 'required|integer|min:1|max:240',
            'status_default_alfa' => 'required|in:alfa,alpa',
        ]);

        $before = AttendanceSettingService::all();
        $logoPath = $before['logo_sekolah'];

        if ($request->hasFile('logo_sekolah')) {
            $filename = 'logo-sekolah-'.now()->format('YmdHis').'.'.$request->file('logo_sekolah')->getClientOriginalExtension();
            $request->file('logo_sekolah')->move(public_path('img'), $filename);
            $logoPath = 'img/'.$filename;
        }

        AttendanceSettingService::setMany([
            'nama_sekolah' => $request->nama_sekolah,
            'logo_sekolah' => $logoPath,
            'jam_masuk' => $request->jam_masuk.':00',
            'batas_telat' => $request->batas_telat.':00',
            'jam_pulang' => $request->jam_pulang.':00',
            'masa_aktif_qr' => (string) $request->masa_aktif_qr,
            'status_default_alfa' => $request->status_default_alfa,
        ]);

        AuditLogger::record('update', 'attendance_settings', null, 'Pengaturan sistem diupdate', $before, AttendanceSettingService::all(), $request);

        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    });

    Route::get('/dashboard/admin/audit-log', function (Request $request) {
        $user = session('user');
        $filters = [
            'q' => $request->get('q', ''),
            'aksi' => $request->get('aksi', ''),
        ];

        $query = DB::table('audit_logs')->latest('id');

        if ($filters['q']) {
            $query->where(function ($search) use ($filters) {
                $search->where('user_name', 'like', '%'.$filters['q'].'%')
                    ->orWhere('aksi', 'like', '%'.$filters['q'].'%')
                    ->orWhere('judul', 'like', '%'.$filters['q'].'%')
                    ->orWhere('tabel', 'like', '%'.$filters['q'].'%');
            });
        }

        if ($filters['aksi']) {
            $query->where('aksi', $filters['aksi']);
        }

        $logs = $query->paginate(20)->withQueryString();
        $aksiList = DB::table('audit_logs')->select('aksi')->distinct()->orderBy('aksi')->pluck('aksi');

        return view('dashboard.audit_log', compact('user', 'logs', 'filters', 'aksiList'));
    });

    Route::get('/dashboard/admin/tahun-ajaran', function () {
        $user = session('user');
        $tahunAjaran = DB::table('tahun_ajarans')
            ->orderByDesc('aktif')
            ->orderByDesc('tanggal_mulai')
            ->get();

        return view('dashboard.tahun_ajaran.index', compact('user', 'tahunAjaran'));
    });

    Route::get('/dashboard/admin/tahun-ajaran/create', function () {
        $user = session('user');

        return view('dashboard.tahun_ajaran.create', compact('user'));
    });

    Route::post('/dashboard/admin/tahun-ajaran/store', function (Request $request) {
        $request->validate([
            'nama' => 'required',
            'semester' => 'required|in:ganjil,genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $exists = DB::table('tahun_ajarans')
            ->where('nama', $request->nama)
            ->where('semester', $request->semester)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Tahun ajaran dan semester tersebut sudah ada.');
        }

        $overlap = DB::table('tahun_ajarans')
            ->whereDate('tanggal_mulai', '<=', $request->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $request->tanggal_mulai)
            ->exists();

        if ($overlap) {
            return back()
                ->withInput()
                ->with('error', 'Rentang tanggal bentrok dengan tahun ajaran lain.');
        }

        if ($request->has('aktif')) {
            DB::table('tahun_ajarans')->update(['aktif' => false, 'updated_at' => now()]);
        }

        DB::table('tahun_ajarans')->insert([
            'nama' => $request->nama,
            'semester' => $request->semester,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'aktif' => $request->has('aktif'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/dashboard/admin/tahun-ajaran')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    });

    Route::get('/dashboard/admin/tahun-ajaran/edit/{id}', function ($id) {
        $user = session('user');
        $tahun = DB::table('tahun_ajarans')->where('id', $id)->first();

        abort_if(! $tahun, 404);

        return view('dashboard.tahun_ajaran.edit', compact('user', 'tahun'));
    });

    Route::post('/dashboard/admin/tahun-ajaran/update/{id}', function (Request $request, $id) {
        $request->validate([
            'nama' => 'required',
            'semester' => 'required|in:ganjil,genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $exists = DB::table('tahun_ajarans')
            ->where('nama', $request->nama)
            ->where('semester', $request->semester)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Tahun ajaran dan semester tersebut sudah ada.');
        }

        $overlap = DB::table('tahun_ajarans')
            ->where('id', '!=', $id)
            ->whereDate('tanggal_mulai', '<=', $request->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $request->tanggal_mulai)
            ->exists();

        if ($overlap) {
            return back()
                ->withInput()
                ->with('error', 'Rentang tanggal bentrok dengan tahun ajaran lain.');
        }

        if ($request->has('aktif')) {
            DB::table('tahun_ajarans')->where('id', '!=', $id)->update(['aktif' => false, 'updated_at' => now()]);
        }

        $before = DB::table('tahun_ajarans')->where('id', $id)->first();

        DB::table('tahun_ajarans')
            ->where('id', $id)
            ->update([
                'nama' => $request->nama,
                'semester' => $request->semester,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'aktif' => $request->has('aktif'),
                'updated_at' => now(),
            ]);
        AuditLogger::record('update', 'tahun_ajarans', (int) $id, 'Tahun ajaran diupdate', $before, DB::table('tahun_ajarans')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/tahun-ajaran')->with('success', 'Tahun ajaran berhasil diupdate.');
    });

    Route::post('/dashboard/admin/tahun-ajaran/{id}/aktif', function ($id) {
        abort_if(! DB::table('tahun_ajarans')->where('id', $id)->exists(), 404);

        DB::table('tahun_ajarans')->update(['aktif' => false, 'updated_at' => now()]);
        DB::table('tahun_ajarans')->where('id', $id)->update(['aktif' => true, 'updated_at' => now()]);

        return back()->with('success', 'Tahun ajaran aktif berhasil diganti.');
    });

    Route::get('/dashboard/admin/tahun-ajaran/delete/{id}', function ($id) {
        $tahun = DB::table('tahun_ajarans')->where('id', $id)->first();

        abort_if(! $tahun, 404);

        if ($tahun->aktif) {
            return back()->with('error', 'Tahun ajaran aktif tidak bisa dihapus.');
        }

        DB::table('tahun_ajarans')->where('id', $id)->delete();
        AuditLogger::record('delete', 'tahun_ajarans', (int) $id, 'Tahun ajaran dihapus', $tahun, null, request());

        return back()->with('success', 'Tahun ajaran berhasil dihapus.');
    });

    Route::get('/dashboard/admin/rekap/guru-piket', function (Request $request) {
        $user = session('user');
        $hari = $request->get('hari');
        $status = $request->get('status');

        $query = DB::table('guru_pikets as gp')
            ->join('users as g', 'g.id', '=', 'gp.guru_id')
            ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
            ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
            ->select(
                'gp.*',
                'g.nama as guru_utama',
                'g1.nama as guru_pengganti',
                'g2.nama as guru_pengganti2'
            );

        if ($hari) {
            $query->where('gp.hari', strtolower($hari));
        }

        if ($status) {
            $query->where('gp.status', $status);
        }

        $data = $query
            ->orderBy('gp.hari')
            ->orderBy('gp.jam_mulai')
            ->orderBy('g.nama')
            ->get();

        return view('dashboard.rekap.guru_piket', compact('user', 'data', 'hari', 'status'));
    });

    Route::get('/dashboard/admin/rekap/jadwal-digantikan', function (Request $request) {
        $user = session('user');
        $hari = $request->get('hari');
        $alasan = $request->get('alasan');

        $query = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->where('j.status_guru', 'digantikan')
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as guru_utama',
                'gp.nama as guru_pengganti'
            );

        if ($hari) {
            $query->where('j.hari', $hari);
        }

        if ($alasan) {
            $query->where('j.alasan_tidak_hadir', $alasan);
        }

        $data = $query
            ->orderBy('j.hari')
            ->orderBy('j.jam_mulai')
            ->get();

        return view('dashboard.rekap.jadwal_digantikan', compact('user', 'data', 'hari', 'alasan'));
    });

    Route::get('/dashboard/admin/rekap/absensi-mapel', function (Request $request) {
        $user = session('user');
        $tanggal = $request->get('tanggal');
        $kelasId = $request->get('kelas_id');
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id');

        $query = DB::table('absensi_mapels as a')
            ->join('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->select(
                'a.*',
                's.nama as nama_siswa',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as guru_utama',
                'gp.nama as guru_pengganti',
                'j.status_guru',
                'j.alasan_tidak_hadir',
                'j.hari',
                'j.jam_mulai',
                'j.jam_selesai'
            );

        if ($tanggal) {
            $query->whereDate('a.tanggal', $tanggal);
        }

        if ($kelasId) {
            $query->where('s.kelas_id', $kelasId);
        }

        if ($tahunAjaranId && Schema::hasColumn('absensi_mapels', 'tahun_ajaran_id')) {
            $query->where('a.tahun_ajaran_id', $tahunAjaranId);
        }

        $data = $query
            ->latest('a.tanggal')
            ->orderBy('k.nama_kelas')
            ->orderBy('s.nama')
            ->get();

        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();

        return view('dashboard.rekap.absensi_mapel', compact('user', 'data', 'tanggal', 'kelasId', 'kelas', 'tahunAjaran', 'tahunAjaranId'));
    });

    Route::get('/dashboard/admin/absensi', function (Request $request) {
        wajibSuperadmin();

        $user = session('user');
        $filters = $request->only(['tanggal', 'kelas_id', 'status', 'search', 'tahun_ajaran_id']);
        $tahunAjaranId = $filters['tahun_ajaran_id'] ?? tahunAjaranAktifId();

        $query = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->select('a.*', 's.nama as nama_siswa', 's.nis', 'k.nama_kelas', 'j.nama_jurusan');

        if (! empty($filters['tanggal'])) {
            $query->whereDate('a.tanggal', $filters['tanggal']);
        }

        if (! empty($filters['kelas_id'])) {
            $query->where('s.kelas_id', $filters['kelas_id']);
        }

        if (! empty($filters['status'])) {
            $query->where(function ($status) use ($filters) {
                $status->where('a.status_masuk', $filters['status'])
                    ->orWhere('a.status_pulang', $filters['status']);
            });
        }

        if (! empty($filters['search'])) {
            $query->where(function ($search) use ($filters) {
                $search->where('s.nama', 'like', '%'.$filters['search'].'%')
                    ->orWhere('s.nis', 'like', '%'.$filters['search'].'%');
            });
        }

        if ($tahunAjaranId && Schema::hasColumn('absensis', 'tahun_ajaran_id')) {
            $query->where('a.tahun_ajaran_id', $tahunAjaranId);
        }

        $data = $query->latest('a.tanggal')->orderBy('k.nama_kelas')->orderBy('s.nama')->paginate(25)->withQueryString();
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();

        return view('dashboard.absensi_admin.index', compact('user', 'data', 'kelas', 'tahunAjaran', 'filters', 'tahunAjaranId'));
    });

    Route::get('/dashboard/admin/absensi/create', function () {
        wajibSuperadmin();

        $user = session('user');
        $absensi = null;
        $mode = 'create';
        $siswa = User::where('role', 'siswa')->orderBy('nama')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = tahunAjaranAktifId();

        return view('dashboard.absensi_admin.form', compact('user', 'absensi', 'mode', 'siswa', 'tahunAjaran', 'tahunAjaranId'));
    });

    Route::post('/dashboard/admin/absensi/store', function (Request $request) {
        wajibSuperadmin();

        $request->validate([
            'id_siswa' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable',
            'jam_pulang' => 'nullable',
            'status_masuk' => 'nullable|string|max:30',
            'status_pulang' => 'nullable|string|max:30',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        ]);

        $exists = DB::table('absensis')
            ->where('id_siswa', $request->id_siswa)
            ->whereDate('tanggal', $request->tanggal)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Absensi harian siswa pada tanggal ini sudah ada. Silakan edit data yang sudah ada.');
        }

        $payload = [
            'id_siswa' => $request->id_siswa,
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk ?: null,
            'jam_pulang' => $request->jam_pulang ?: null,
            'status_masuk' => $request->status_masuk ?: null,
            'status_pulang' => $request->status_pulang ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('absensis', 'tahun_ajaran_id')) {
            $payload['tahun_ajaran_id'] = $request->tahun_ajaran_id ?: tahunAjaranAktifId();
        }

        $id = DB::table('absensis')->insertGetId($payload);
        AuditLogger::record('create', 'absensis', (int) $id, 'Absensi harian dibuat superadmin', null, DB::table('absensis')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/absensi')->with('success', 'Absensi harian berhasil ditambahkan.');
    });

    Route::get('/dashboard/admin/absensi/{id}', function ($id) {
        wajibSuperadmin();

        $user = session('user');
        $absensi = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'a.tahun_ajaran_id')
            ->select('a.*', 's.nama as nama_siswa', 's.nis', 'k.nama_kelas', 'j.nama_jurusan', 'ta.nama as tahun_ajaran', 'ta.semester')
            ->where('a.id', $id)
            ->first();

        abort_if(! $absensi, 404);

        return view('dashboard.absensi_admin.view', compact('user', 'absensi'));
    })->whereNumber('id');

    Route::get('/dashboard/admin/absensi/edit/{id}', function ($id) {
        wajibSuperadmin();

        $user = session('user');
        $absensi = DB::table('absensis')->where('id', $id)->first();
        abort_if(! $absensi, 404);

        $mode = 'edit';
        $siswa = User::where('role', 'siswa')->orderBy('nama')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $absensi->tahun_ajaran_id ?? tahunAjaranAktifId();

        return view('dashboard.absensi_admin.form', compact('user', 'absensi', 'mode', 'siswa', 'tahunAjaran', 'tahunAjaranId'));
    })->whereNumber('id');

    Route::post('/dashboard/admin/absensi/update/{id}', function (Request $request, $id) {
        wajibSuperadmin();

        $request->validate([
            'id_siswa' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable',
            'jam_pulang' => 'nullable',
            'status_masuk' => 'nullable|string|max:30',
            'status_pulang' => 'nullable|string|max:30',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        ]);

        $old = DB::table('absensis')->where('id', $id)->first();
        abort_if(! $old, 404);

        $duplicate = DB::table('absensis')
            ->where('id', '!=', $id)
            ->where('id_siswa', $request->id_siswa)
            ->whereDate('tanggal', $request->tanggal)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Data ganda ditolak. Siswa ini sudah punya absensi harian pada tanggal tersebut.');
        }

        $payload = [
            'id_siswa' => $request->id_siswa,
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk ?: null,
            'jam_pulang' => $request->jam_pulang ?: null,
            'status_masuk' => $request->status_masuk ?: null,
            'status_pulang' => $request->status_pulang ?: null,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('absensis', 'tahun_ajaran_id')) {
            $payload['tahun_ajaran_id'] = $request->tahun_ajaran_id ?: tahunAjaranAktifId();
        }

        DB::table('absensis')->where('id', $id)->update($payload);
        AuditLogger::record('update', 'absensis', (int) $id, 'Absensi harian diubah superadmin', $old, DB::table('absensis')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/absensi')->with('success', 'Absensi harian berhasil diperbarui.');
    })->whereNumber('id');

    Route::get('/dashboard/admin/absensi/delete/{id}', function (Request $request, $id) {
        wajibSuperadmin();

        $old = DB::table('absensis')->where('id', $id)->first();
        abort_if(! $old, 404);

        DB::table('absensis')->where('id', $id)->delete();
        AuditLogger::record('delete', 'absensis', (int) $id, 'Absensi harian dihapus superadmin', $old, null, $request);

        return back()->with('success', 'Absensi harian berhasil dihapus.');
    })->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel', function (Request $request) {
        wajibSuperadmin();

        $user = session('user');
        $filters = $request->only(['tanggal', 'kelas_id', 'status', 'search', 'tahun_ajaran_id']);
        $tahunAjaranId = $filters['tahun_ajaran_id'] ?? tahunAjaranAktifId();

        $query = DB::table('absensi_mapels as a')
            ->join('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->join('jadwal_pelajarans as jp', 'jp.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'jp.guru_pengganti_id')
            ->select(
                'a.*',
                's.nama as nama_siswa',
                's.nis',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as guru_utama',
                'gp.nama as guru_pengganti',
                'jp.hari',
                'jp.jam_mulai',
                'jp.jam_selesai',
                'jp.status_guru'
            );

        if (! empty($filters['tanggal'])) {
            $query->whereDate('a.tanggal', $filters['tanggal']);
        }

        if (! empty($filters['kelas_id'])) {
            $query->where('s.kelas_id', $filters['kelas_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('a.status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($search) use ($filters) {
                $search->where('s.nama', 'like', '%'.$filters['search'].'%')
                    ->orWhere('s.nis', 'like', '%'.$filters['search'].'%')
                    ->orWhere('m.nama_mapel', 'like', '%'.$filters['search'].'%')
                    ->orWhere('g.nama', 'like', '%'.$filters['search'].'%');
            });
        }

        if ($tahunAjaranId && Schema::hasColumn('absensi_mapels', 'tahun_ajaran_id')) {
            $query->where('a.tahun_ajaran_id', $tahunAjaranId);
        }

        $data = $query->latest('a.tanggal')->orderBy('k.nama_kelas')->orderBy('s.nama')->paginate(25)->withQueryString();
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();

        return view('dashboard.absensi_mapel_admin.index', compact('user', 'data', 'kelas', 'tahunAjaran', 'filters', 'tahunAjaranId'));
    });

    Route::get('/dashboard/admin/absensi-mapel/create', function () {
        wajibSuperadmin();

        $user = session('user');
        $absensi = null;
        $mode = 'create';
        $siswa = User::where('role', 'siswa')->orderBy('nama')->get();
        $jadwal = DB::table('jadwal_pelajarans as jp')
            ->join('kelas as k', 'k.id', '=', 'jp.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->select('jp.*', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as nama_guru')
            ->orderBy('k.nama_kelas')->orderBy('jp.hari')->orderBy('jp.jam_mulai')
            ->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = tahunAjaranAktifId();

        return view('dashboard.absensi_mapel_admin.form', compact('user', 'absensi', 'mode', 'siswa', 'jadwal', 'tahunAjaran', 'tahunAjaranId'));
    });

    Route::post('/dashboard/admin/absensi-mapel/store', function (Request $request) {
        wajibSuperadmin();

        $request->validate([
            'jadwal_id' => 'required|exists:jadwal_pelajarans,id',
            'siswa_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'jam_scan' => 'nullable',
            'status' => 'required|string|max:30',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        ]);

        $jadwal = DB::table('jadwal_pelajarans')->where('id', $request->jadwal_id)->first();
        $siswa = User::where('role', 'siswa')->where('id', $request->siswa_id)->first();

        if (! $jadwal || ! $siswa || (int) $siswa->kelas_id !== (int) $jadwal->kelas_id) {
            return back()->withInput()->with('error', 'Siswa harus sesuai dengan kelas pada jadwal mapel.');
        }

        $exists = DB::table('absensi_mapels')
            ->where('jadwal_id', $request->jadwal_id)
            ->where('siswa_id', $request->siswa_id)
            ->whereDate('tanggal', $request->tanggal)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Absensi mapel siswa pada jadwal dan tanggal ini sudah ada. Silakan edit data yang sudah ada.');
        }

        $payload = [
            'jadwal_id' => $request->jadwal_id,
            'siswa_id' => $request->siswa_id,
            'tanggal' => $request->tanggal,
            'jam_scan' => $request->jam_scan ?: null,
            'status' => $request->status,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('absensi_mapels', 'tahun_ajaran_id')) {
            $payload['tahun_ajaran_id'] = $request->tahun_ajaran_id ?: ($jadwal->tahun_ajaran_id ?? tahunAjaranAktifId());
        }

        $id = DB::table('absensi_mapels')->insertGetId($payload);
        AuditLogger::record('create', 'absensi_mapels', (int) $id, 'Absensi mapel dibuat superadmin', null, DB::table('absensi_mapels')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/absensi-mapel')->with('success', 'Absensi mapel berhasil ditambahkan.');
    });

    Route::get('/dashboard/admin/absensi-mapel/{id}', function ($id) {
        wajibSuperadmin();

        $user = session('user');
        $absensi = DB::table('absensi_mapels as a')
            ->join('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->join('jadwal_pelajarans as jp', 'jp.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'jp.guru_pengganti_id')
            ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'a.tahun_ajaran_id')
            ->select('a.*', 's.nama as nama_siswa', 's.nis', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as guru_utama', 'gp.nama as guru_pengganti', 'jp.hari', 'jp.jam_mulai', 'jp.jam_selesai', 'ta.nama as tahun_ajaran', 'ta.semester')
            ->where('a.id', $id)
            ->first();

        abort_if(! $absensi, 404);

        return view('dashboard.absensi_mapel_admin.view', compact('user', 'absensi'));
    })->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel/edit/{id}', function ($id) {
        wajibSuperadmin();

        $user = session('user');
        $absensi = DB::table('absensi_mapels')->where('id', $id)->first();
        abort_if(! $absensi, 404);

        $mode = 'edit';
        $siswa = User::where('role', 'siswa')->orderBy('nama')->get();
        $jadwal = DB::table('jadwal_pelajarans as jp')
            ->join('kelas as k', 'k.id', '=', 'jp.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->select('jp.*', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as nama_guru')
            ->orderBy('k.nama_kelas')->orderBy('jp.hari')->orderBy('jp.jam_mulai')
            ->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $absensi->tahun_ajaran_id ?? tahunAjaranAktifId();

        return view('dashboard.absensi_mapel_admin.form', compact('user', 'absensi', 'mode', 'siswa', 'jadwal', 'tahunAjaran', 'tahunAjaranId'));
    })->whereNumber('id');

    Route::post('/dashboard/admin/absensi-mapel/update/{id}', function (Request $request, $id) {
        wajibSuperadmin();

        $request->validate([
            'jadwal_id' => 'required|exists:jadwal_pelajarans,id',
            'siswa_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'jam_scan' => 'nullable',
            'status' => 'required|string|max:30',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        ]);

        $old = DB::table('absensi_mapels')->where('id', $id)->first();
        abort_if(! $old, 404);

        $jadwal = DB::table('jadwal_pelajarans')->where('id', $request->jadwal_id)->first();
        $siswa = User::where('role', 'siswa')->where('id', $request->siswa_id)->first();

        if (! $jadwal || ! $siswa || (int) $siswa->kelas_id !== (int) $jadwal->kelas_id) {
            return back()->withInput()->with('error', 'Siswa harus sesuai dengan kelas pada jadwal mapel.');
        }

        $duplicate = DB::table('absensi_mapels')
            ->where('id', '!=', $id)
            ->where('jadwal_id', $request->jadwal_id)
            ->where('siswa_id', $request->siswa_id)
            ->whereDate('tanggal', $request->tanggal)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Data ganda ditolak. Siswa ini sudah punya absensi mapel pada jadwal dan tanggal tersebut.');
        }

        $payload = [
            'jadwal_id' => $request->jadwal_id,
            'siswa_id' => $request->siswa_id,
            'tanggal' => $request->tanggal,
            'jam_scan' => $request->jam_scan ?: null,
            'status' => $request->status,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('absensi_mapels', 'tahun_ajaran_id')) {
            $payload['tahun_ajaran_id'] = $request->tahun_ajaran_id ?: ($jadwal->tahun_ajaran_id ?? tahunAjaranAktifId());
        }

        DB::table('absensi_mapels')->where('id', $id)->update($payload);
        AuditLogger::record('update', 'absensi_mapels', (int) $id, 'Absensi mapel diubah superadmin', $old, DB::table('absensi_mapels')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/absensi-mapel')->with('success', 'Absensi mapel berhasil diperbarui.');
    })->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel/delete/{id}', function (Request $request, $id) {
        wajibSuperadmin();

        $old = DB::table('absensi_mapels')->where('id', $id)->first();
        abort_if(! $old, 404);

        DB::table('absensi_mapels')->where('id', $id)->delete();
        AuditLogger::record('delete', 'absensi_mapels', (int) $id, 'Absensi mapel dihapus superadmin', $old, null, $request);

        return back()->with('success', 'Absensi mapel berhasil dihapus.');
    })->whereNumber('id');

    Route::get('/dashboard/admin/rekap/jadwal-guru-mapel', function (Request $request) {
        $user = session('user');
        $guruId = $request->get('guru_id');
        $hari = $request->get('hari');

        $query = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as guru_utama',
                'gp.nama as guru_pengganti'
            );

        if ($guruId) {
            $query->where('j.guru_id', $guruId);
        }

        if ($hari) {
            $query->where('j.hari', $hari);
        }

        $data = $query
            ->orderBy('g.nama')
            ->orderBy('j.hari')
            ->orderBy('j.jam_mulai')
            ->get();

        $guru = User::where('role', 'guru')->orderBy('nama')->get();

        return view('dashboard.rekap.jadwal_guru_mapel', compact('user', 'data', 'guru', 'guruId', 'hari'));
    });
});

/*
|--------------------------------------------------------------------------
| FITUR TAMBAHAN ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware('webrole:admin')->group(function () {

    Route::get('/dashboard/admin/users', function () {
        wajibSuperadmin();

        $user = session('user');
        $users = User::with('kelasRelasi')->orderBy('role')->orderBy('nama')->get();

        return view('dashboard.users_admin.index', compact('user', 'users'));
    });

    Route::get('/dashboard/admin/users/create', function () {
        wajibSuperadmin();

        $user = session('user');
        $target = null;
        $mode = 'create';
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

        return view('dashboard.users_admin.form', compact('user', 'target', 'mode', 'kelas'));
    })->whereNumber('id');

    Route::post('/dashboard/admin/users/store', function (Request $request) {
        wajibSuperadmin();

        $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:4',
            'role' => 'required|in:admin,guru,siswa',
            'kelas_id' => 'nullable|exists:kelas,id',
            'admin_level' => 'nullable|in:superadmin',
        ]);

        if ($request->role === 'admin') {
            return back()->withInput()->with('error', 'Admin sistem hanya satu, yaitu Devi sebagai superadmin.');
        }

        $newUser = User::create([
            'nama' => $request->nama,
            'nis' => $request->nis,
            'nuptk' => $request->nuptk,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'admin_level' => $request->role === 'admin' ? ($request->admin_level ?: null) : null,
            'kelas_id' => $request->role === 'siswa' ? $request->kelas_id : null,
            'no_ortu' => $request->no_ortu,
            'nama_ortu' => $request->nama_ortu,
            'aktif' => $request->has('aktif') ? 1 : 0,
        ]);
        AuditLogger::record('create', 'users', $newUser->id, 'User dibuat superadmin', null, $newUser, $request);

        return redirect('/dashboard/admin/users')->with('success', 'User berhasil ditambahkan.');
    });

    Route::get('/dashboard/admin/users/edit/{id}', function ($id) {
        wajibSuperadmin();

        $user = session('user');
        $target = User::findOrFail($id);
        $mode = 'edit';
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

        return view('dashboard.users_admin.form', compact('user', 'target', 'mode', 'kelas'));
    });

    Route::post('/dashboard/admin/users/update/{id}', function (Request $request, $id) {
        wajibSuperadmin();

        $target = User::findOrFail($id);
        $isMainSuperadmin = $target->role === 'admin' && $target->admin_level === 'superadmin';

        $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$target->id,
            'password' => 'nullable|string|min:4',
            'role' => 'required|in:admin,guru,siswa',
            'kelas_id' => 'nullable|exists:kelas,id',
            'admin_level' => 'nullable|in:superadmin',
        ]);

        if (! $isMainSuperadmin && $request->role === 'admin') {
            return back()->withInput()->with('error', 'Tidak bisa menambah admin baru. Admin sistem hanya Devi sebagai superadmin.');
        }

        $before = $target->replicate();
        $target->fill([
            'nama' => $request->nama,
            'nis' => $request->nis,
            'nuptk' => $request->nuptk,
            'username' => $request->username,
            'role' => $isMainSuperadmin ? 'admin' : $request->role,
            'admin_level' => $isMainSuperadmin ? 'superadmin' : null,
            'kelas_id' => (! $isMainSuperadmin && $request->role === 'siswa') ? $request->kelas_id : null,
            'no_ortu' => $request->no_ortu,
            'nama_ortu' => $request->nama_ortu,
            'aktif' => $request->has('aktif') ? 1 : 0,
        ]);

        if ($request->filled('password')) {
            $target->password = Hash::make($request->password);
        }

        $target->save();

        if ((int) session('user')->id === (int) $target->id) {
            session(['user' => $target->fresh()]);
        }

        AuditLogger::record('update', 'users', $target->id, 'User diubah superadmin', $before, $target->fresh(), $request);

        return redirect('/dashboard/admin/users')->with('success', 'User berhasil diperbarui.');
    })->whereNumber('id');

    Route::get('/dashboard/admin/users/delete/{id}', function (Request $request, $id) {
        wajibSuperadmin();

        $target = User::findOrFail($id);

        if ($target->role === 'admin' && $target->admin_level === 'superadmin') {
            return back()->with('error', 'Superadmin aktif tidak boleh dihapus agar akses sistem tetap aman.');
        }

        $before = $target->replicate();
        $target->delete();
        AuditLogger::record('delete', 'users', (int) $id, 'User dihapus superadmin', $before, null, $request);

        return back()->with('success', 'User berhasil dihapus.');
    })->whereNumber('id');

    Route::get(
        '/dashboard/admin/siswa/import',
        [AdminFeatureController::class, 'importSiswaForm']
    );

    Route::post(
        '/dashboard/admin/siswa/import',
        [AdminFeatureController::class, 'importSiswa']
    );

    Route::get(
        '/dashboard/admin/siswa/template',
        [AdminFeatureController::class, 'downloadTemplateSiswa',
        ]);

    Route::get(
        '/dashboard/admin/jadwal/import',
        [AdminFeatureController::class, 'importJadwalForm']
    );

    Route::post(
        '/dashboard/admin/jadwal/import',
        [AdminFeatureController::class, 'importJadwal']
    );

    Route::get(
        '/dashboard/admin/jadwal/template',
        [AdminFeatureController::class, 'downloadTemplateJadwal']
    );

    Route::get(
        '/dashboard/admin/kalender-sekolah/template',
        [AdminFeatureController::class, 'downloadTemplateKalender']
    );

    Route::post(
        '/dashboard/admin/kalender-sekolah/import',
        [AdminFeatureController::class, 'importKalender']
    );

    Route::get(
        '/dashboard/admin/kalender-sekolah/export',
        [AdminFeatureController::class, 'exportKalender']
    );

    /*
    |--------------------------------------------------------------------------
    | REKAP ABSENSI
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/absensi/rekap',
        [AdminFeatureController::class, 'rekapAbsensi']
    )
        ->name(
            'rekap.absensi'
        );

    /*
    |--------------------------------------------------------------------------
    | EXPORT EXCEL
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/absensi/export',
        [AdminFeatureController::class, 'exportAbsensi']
    )
        ->name(
            'export.absensi'
        );

    /*
    |--------------------------------------------------------------------------



    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD USER
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/users/{id}/reset-password',
        [AdminFeatureController::class, 'resetPasswordForm']
    );

    Route::post(
        '/dashboard/admin/users/{id}/reset-password',
        [AdminFeatureController::class, 'resetPassword']
    );

    Route::post(
        '/dashboard/admin/users/{id}/toggle-active',
        [AdminFeatureController::class, 'toggleActive']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT GURU
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/guru/edit/{id}',
        [AdminFeatureController::class, 'editGuru']
    );

    Route::post(
        '/dashboard/admin/guru/update/{id}',
        [AdminFeatureController::class, 'updateGuru']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT KELAS
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/kelas/edit/{id}',
        [AdminFeatureController::class, 'editKelas']
    );

    Route::post(
        '/dashboard/admin/kelas/update/{id}',
        [AdminFeatureController::class, 'updateKelas']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT JURUSAN
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/jurusan/edit/{id}',
        [AdminFeatureController::class, 'editJurusan']
    );

    Route::post(
        '/dashboard/admin/jurusan/update/{id}',
        [AdminFeatureController::class, 'updateJurusan']
    );

});

/*
|--------------------------------------------------------------------------
| LIST GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru', function () {

    $user = session('user');

    $guru = User::where('role', 'guru')
        ->latest('id')
        ->get();

    return view('dashboard.guru.index', compact('user', 'guru'));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM TAMBAH GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru/create', function () {

    $user = session('user');

    return view('dashboard.guru.create', compact('user'));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN GURU
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/guru/store', function (Request $request) {

    $request->validate([
        'nama' => 'required',
        'nuptk' => 'nullable',
        'username' => 'required|unique:users,username',
        'password' => 'required',
    ]);

    $guru = User::create([
        'nama' => $request->nama,
        'nuptk' => $request->nuptk,
        'username' => $request->username,
        'password' => Hash::make($request->password),
        'role' => 'guru',
        'no_ortu' => null,
        'nama_ortu' => null,
    ]);
    AuditLogger::record('create', 'users', $guru->id, 'Data guru ditambahkan', null, $guru, $request);

    return redirect('/dashboard/admin/guru');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru/delete/{id}', function ($id) {

    $before = User::where('id', $id)->where('role', 'guru')->first();

    User::where('id', $id)
        ->where('role', 'guru')
        ->delete();
    AuditLogger::record('delete', 'users', (int) $id, 'Data guru dihapus', $before, null, request());

    return redirect('/dashboard/admin/guru');

})->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| LIST KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas', function () {

    $user = session('user');

    $kelas = DB::table('kelas as k')

        ->leftJoin('users as u', 'u.id', '=', 'k.wali_kelas_id')

        ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')

        ->select(
            'k.*',
            'u.nama as nama_wali',
            'j.nama_jurusan',
            'j.kode_jurusan'
        )

        ->orderBy('k.nama_kelas')

        ->get();

    return view('dashboard.kelas.index', compact(
        'user',
        'kelas'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM TAMBAH KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas/create', function () {

    $user = session('user');

    $guru = User::where('role', 'guru')
        ->orderBy('nama')
        ->get();

    $jurusan = DB::table('jurusan')
        ->orderBy('kode_jurusan')
        ->get();

    return view('dashboard.kelas.create', compact(
        'user',
        'guru',
        'jurusan'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN KELAS
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/kelas/store', function (Request $request) {

    $request->validate([
        'nama_kelas' => 'required|unique:kelas,nama_kelas',
        'jurusan_id' => 'required',
    ]);

    if ($request->filled('wali_kelas_id')) {
        $waliDipakai = DB::table('kelas')
            ->where('wali_kelas_id', $request->wali_kelas_id)
            ->exists();

        if ($waliDipakai) {
            return back()->with('error', 'Guru sudah menjadi wali kelas lain');
        }
    }

    DB::table('kelas')->insert([

        'nama_kelas' => $request->nama_kelas,

        // tambahan jurusan
        'jurusan_id' => $request->jurusan_id,

        'wali_kelas_id' => $request->wali_kelas_id,

        'created_at' => now(),
        'updated_at' => now(),

    ]);

    return redirect('/dashboard/admin/kelas')
        ->with('success', 'Kelas berhasil ditambahkan');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas/delete/{id}', function ($id) {

    DB::table('kelas')
        ->where('id', $id)
        ->delete();

    return redirect('/dashboard/admin/kelas')
        ->with('success', 'Kelas berhasil dihapus');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| LIST JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal', function () {

    $user = session('user');

    $jadwal = DB::table('jadwal_pelajarans as j')

        ->join(
            'kelas as k',
            'k.id',
            '=',
            'j.kelas_id'
        )

        ->join(
            'mapels as m',
            'm.id',
            '=',
            'j.mapel_id'
        )

        ->join(
            'users as g',
            'g.id',
            '=',
            'j.guru_id'
        )

        /*
        ==========================
        TAMBAHAN
        ==========================
        */

        ->leftJoin(
            'users as gp',
            'gp.id',
            '=',
            'j.guru_pengganti_id'
        )

        ->select(

            'j.*',

            'k.nama_kelas',

            'm.nama_mapel',

            'g.nama as nama_guru',

            'gp.nama as nama_guru_pengganti',

            'j.keterangan',

            'j.status_guru'

        )

        ->orderBy(
            'j.hari'
        )

        ->orderBy(
            'j.jam_mulai'
        )

        ->get();

    return view(

        'dashboard.jadwal.index',

        compact(

            'user',

            'jadwal'

        )

    );

})
    ->middleware(

        'webrole:admin'

    );
/*
|--------------------------------------------------------------------------
| FORM TAMBAH JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal/create', function () {

    $user = session('user');

    $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

    $mapels = DB::table('mapels')->orderBy('nama_mapel')->get();

    $guru = User::where('role', 'guru')
        ->orderBy('nama')
        ->get();
    $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
    $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

    return view('dashboard.jadwal.create', compact(
        'user',
        'kelas',
        'mapels',
        'guru',
        'tahunAjaran',
        'tahunAjaranAktif'
    ));

})->middleware('webrole:admin');

Route::get('/dashboard/admin/jadwal/bentrok', function (Request $request) {
    $user = session('user');
    $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
    $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
    $bentrok = collect();

    $jadwal = DB::table('jadwal_pelajarans as j')
        ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
        ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
        ->join('users as g', 'g.id', '=', 'j.guru_id')
        ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'j.tahun_ajaran_id')
        ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('j.tahun_ajaran_id', $tahunAjaranId))
        ->select('j.*', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as nama_guru', 'ta.nama as tahun_nama', 'ta.semester')
        ->get();

    foreach ($jadwal as $index => $a) {
        foreach ($jadwal->slice($index + 1) as $b) {
            if (strtolower($a->hari) !== strtolower($b->hari) || $a->tahun_ajaran_id != $b->tahun_ajaran_id) {
                continue;
            }

            $overlap = $a->jam_mulai < $b->jam_selesai && $a->jam_selesai > $b->jam_mulai;

            if (! $overlap) {
                continue;
            }

            if ($a->kelas_id == $b->kelas_id || $a->guru_id == $b->guru_id) {
                $bentrok->push([
                    'tipe' => $a->kelas_id == $b->kelas_id ? 'Kelas' : 'Guru',
                    'tahun_ajaran' => ($a->tahun_nama ?: '-').' '.($a->semester ? ucfirst($a->semester) : ''),
                    'hari' => $a->hari,
                    'jam' => $a->jam_mulai.'-'.$a->jam_selesai.' bentrok '.$b->jam_mulai.'-'.$b->jam_selesai,
                    'detail_1' => $a->nama_kelas.' | '.$a->nama_mapel.' | '.$a->nama_guru,
                    'detail_2' => $b->nama_kelas.' | '.$b->nama_mapel.' | '.$b->nama_guru,
                ]);
            }
        }
    }

    $piket = DB::table('guru_pikets as p')
        ->join('users as g', 'g.id', '=', 'p.guru_id')
        ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'p.tahun_ajaran_id')
        ->when($tahunAjaranId && Schema::hasColumn('guru_pikets', 'tahun_ajaran_id'), fn ($query) => $query->where('p.tahun_ajaran_id', $tahunAjaranId))
        ->select('p.*', 'g.nama as nama_guru', 'ta.nama as tahun_nama', 'ta.semester')
        ->get();

    foreach ($piket as $p) {
        foreach ($jadwal as $j) {
            if (strtolower($p->hari) !== strtolower($j->hari) || $p->tahun_ajaran_id != $j->tahun_ajaran_id) {
                continue;
            }

            $overlap = $p->jam_mulai < $j->jam_selesai && $p->jam_selesai > $j->jam_mulai;

            if ($overlap && in_array($p->guru_id, [$j->guru_id, $j->guru_pengganti_id])) {
                $bentrok->push([
                    'tipe' => 'Piket vs Mengajar',
                    'tahun_ajaran' => ($p->tahun_nama ?: '-').' '.($p->semester ? ucfirst($p->semester) : ''),
                    'hari' => $p->hari,
                    'jam' => $p->jam_mulai.'-'.$p->jam_selesai.' bentrok '.$j->jam_mulai.'-'.$j->jam_selesai,
                    'detail_1' => 'Piket: '.$p->nama_guru,
                    'detail_2' => 'Mengajar: '.$j->nama_guru.' | '.$j->nama_kelas.' | '.$j->nama_mapel,
                ]);
            }
        }
    }

    return view('dashboard.jadwal.bentrok', compact('user', 'tahunAjaran', 'tahunAjaranId', 'bentrok'));
})->middleware('webrole:admin');

Route::get('/dashboard/admin/jadwal/edit/{id}', function ($id) {

    $user = session('user');

    $jadwal = DB::table('jadwal_pelajarans')
        ->where('id', $id)
        ->first();

    if (! $jadwal) {
        abort(404);
    }

    $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
    $mapels = DB::table('mapels')->orderBy('nama_mapel')->get();
    $guru = User::where('role', 'guru')->orderBy('nama')->get();
    $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
    $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

    return view('dashboard.jadwal.edit', compact(
        'user',
        'jadwal',
        'kelas',
        'mapels',
        'guru',
        'tahunAjaran',
        'tahunAjaranAktif'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
|/*
|--------------------------------------------------------------------------
| SIMPAN JADWAL
|--------------------------------------------------------------------------
*/

Route::post(

    '/dashboard/admin/jadwal/store',

    function (

        Request $request

    ) {

        $request->validate([

            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',

            'kelas_id' => 'required',

            'hari' => 'required',

            'jam_mulai' => 'required',

            'jam_selesai' => 'required',

            'mapel_id' => 'required',

            'guru_id' => 'required',

            'guru_pengganti_id' => 'nullable',

            'keterangan' => 'nullable',

        ]);

        if ($pesanBentrok = validasiBentrokJadwalPelajaran($request)) {
            return back()
                ->withInput()
                ->with('error', $pesanBentrok);
        }

        if ($pesanLibur = validasiJadwalSaatLibur($request)) {
            return back()
                ->withInput()
                ->with('error', $pesanLibur);
        }

        $newId = DB::table(

            'jadwal_pelajarans'

        )
            ->insertGetId([

                'kelas_id' => $request->kelas_id,

                'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),

                'hari' => $request->hari,

                'jam_mulai' => $request->jam_mulai,

                'jam_selesai' => $request->jam_selesai,

                'mapel_id' => $request->mapel_id,

                'guru_id' => $request->guru_id,

                /*
        ==================================
        Guru pengganti
        ==================================
        */

                'guru_pengganti_id' => $request->guru_pengganti_id

                ??

                null,

                /*
        ==================================
        PENTING:
        Awal = BELUM PILIH STATUS
        ==================================
        */

                'status_guru' => null,

                'alasan_tidak_hadir' => null,

                'keterangan' => $request->keterangan

                ??

                null,

                'created_at' => now(),

                'updated_at' => now(),

            ]);
        AuditLogger::record('create', 'jadwal_pelajarans', (int) $newId, 'Jadwal pelajaran ditambahkan', null, DB::table('jadwal_pelajarans')->where('id', $newId)->first(), $request);

        return redirect(

            '/dashboard/admin/jadwal'

        )
            ->with(

                'success',

                'Jadwal berhasil ditambahkan'

            );

    })
    ->middleware(

        'webrole:admin'

    );

Route::post('/dashboard/admin/jadwal/update/{id}', function (Request $request, $id) {

    $request->validate([
        'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        'kelas_id' => 'required',
        'hari' => 'required',
        'jam_mulai' => 'required',
        'jam_selesai' => 'required',
        'mapel_id' => 'required',
        'guru_id' => 'required',
        'guru_pengganti_id' => 'nullable',
        'keterangan' => 'nullable',
    ]);

    if ($pesanBentrok = validasiBentrokJadwalPelajaran($request, (int) $id)) {
        return back()
            ->withInput()
            ->with('error', $pesanBentrok);
    }

    if ($pesanLibur = validasiJadwalSaatLibur($request)) {
        return back()
            ->withInput()
            ->with('error', $pesanLibur);
    }

    $before = DB::table('jadwal_pelajarans')->where('id', $id)->first();

    DB::table('jadwal_pelajarans')
        ->where('id', $id)
        ->update([
            'kelas_id' => $request->kelas_id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),
            'hari' => $request->hari,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'mapel_id' => $request->mapel_id,
            'guru_id' => $request->guru_id,
            'guru_pengganti_id' => $request->guru_pengganti_id ?: null,
            'keterangan' => $request->keterangan ?: null,
            'updated_at' => now(),
        ]);
    AuditLogger::record('update', 'jadwal_pelajarans', (int) $id, 'Jadwal pelajaran diupdate', $before, DB::table('jadwal_pelajarans')->where('id', $id)->first(), $request);

    return redirect('/dashboard/admin/jadwal')
        ->with('success', 'Jadwal berhasil diupdate');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal/delete/{id}', function ($id) {

    $before = DB::table('jadwal_pelajarans')->where('id', $id)->first();

    DB::table('jadwal_pelajarans')
        ->where('id', $id)
        ->delete();
    AuditLogger::record('delete', 'jadwal_pelajarans', (int) $id, 'Jadwal pelajaran dihapus', $before, null, request());

    return redirect('/dashboard/admin/jadwal');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| LIST SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa', function (Request $request) {

    $user = session('user');

    $query = DB::table('users as s')

        /*
        |--------------------------------------------------------------------------
        | RELASI KELAS
        |--------------------------------------------------------------------------
        */
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')

        /*
        |--------------------------------------------------------------------------
        | RELASI JURUSAN
        |--------------------------------------------------------------------------
        */
        ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')

        /*
        |--------------------------------------------------------------------------
        | RELASI WALI KELAS
        |--------------------------------------------------------------------------
        */
        ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')

        ->select(
            's.*',

            // kelas
            'k.nama_kelas',

            // wali kelas
            'w.nama as nama_wali',

            // jurusan
            'j.nama_jurusan',
            'j.kode_jurusan'
        )

        ->where('s.role', 'siswa');

    /*
    |--------------------------------------------------------------------------
    /*
|--------------------------------------------------------------------------
| SEARCH NAMA / NIS
|--------------------------------------------------------------------------
*/
    if ($request->search) {

        $query->where(function ($q) use ($request) {

            $q->where(
                's.nama',
                'like',
                '%'.$request->search.'%'
            )
                ->orWhere(
                    's.nis',
                    'like',
                    '%'.$request->search.'%'
                );

        });

    }

    /*
    |--------------------------------------------------------------------------
    | FILTER JURUSAN
    |--------------------------------------------------------------------------
    */
    if ($request->jurusan) {

        $query->where(
            'j.kode_jurusan',
            $request->jurusan
        );

    }

    /*
    |--------------------------------------------------------------------------
    | FILTER TINGKAT
    |--------------------------------------------------------------------------
    */
    if ($request->tingkat) {

        $query->where(
            'k.nama_kelas',
            'like',
            $request->tingkat.'%'
        );

    }

    $siswa = $query
        ->latest('s.id')
        ->get();

    return view('dashboard.siswa.index', compact(
        'user',
        'siswa'
    ));

})->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| FORM TAMBAH SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/create', function () {

    $user = session('user');

    /*
    |--------------------------------------------------------------------------
    | JURUSAN
    |--------------------------------------------------------------------------
    */
    $jurusan = DB::table('jurusan')
        ->orderBy('kode_jurusan')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | KELAS + WALI KELAS
    |--------------------------------------------------------------------------
    */
    $kelas = DB::table('kelas as k')

        ->leftJoin('users as u', 'u.id', '=', 'k.wali_kelas_id')

        ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')

        ->select(
            'k.id',
            'k.nama_kelas',
            'k.jurusan_id',
            'u.nama as nama_wali',
            'j.kode_jurusan'
        )

        ->orderBy('k.nama_kelas')

        ->get();

    return view('dashboard.siswa.create', compact(
        'user',
        'jurusan',
        'kelas'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| SIMPAN SISWA
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/siswa/store', function (Request $request) {

    $request->validate([
        'nama' => 'required',

        'nis' => 'required|unique:users,nis', // TAMBAH

        'username' => 'required|unique:users,username',
        'password' => 'required',
        'kelas_id' => 'required',
    ]);

    $siswa = User::create([

        'nama' => $request->nama,

        'nis' => $request->nis, // TAMBAH

        'username' => $request->username,

        'password' => Hash::make($request->password),

        'role' => 'siswa',

        /*
        |--------------------------------------------------------------------------
        | RELASI KELAS
        |--------------------------------------------------------------------------
        */
        'kelas_id' => $request->kelas_id,

        /*
        |--------------------------------------------------------------------------
        | NO ORANG TUA
        |--------------------------------------------------------------------------
        */
        'no_ortu' => $request->no_ortu,
        'nama_ortu' => $request->nama_ortu,

        'created_at' => now(),
        'updated_at' => now(),

    ]);
    AuditLogger::record('create', 'users', $siswa->id, 'Data siswa ditambahkan', null, $siswa, $request);

    return redirect('/dashboard/admin/siswa')
        ->with('success', 'Siswa berhasil ditambahkan');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM EDIT SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/edit/{id}', function ($id) {

    $user = session('user');

    $siswa = User::findOrFail($id);

    /*
    |--------------------------------------------------------------------------
    | JURUSAN
    |--------------------------------------------------------------------------
    */
    $jurusan = DB::table('jurusan')
        ->orderBy('kode_jurusan')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | KELAS
    |--------------------------------------------------------------------------
    */
    $kelas = DB::table('kelas as k')

        ->leftJoin('users as u', 'u.id', '=', 'k.wali_kelas_id')

        ->select(
            'k.id',
            'k.nama_kelas',
            'k.jurusan_id',
            'u.nama as nama_wali'
        )

        ->orderBy('k.nama_kelas')

        ->get();

    return view('dashboard.siswa.edit', compact(
        'user',
        'siswa',
        'jurusan',
        'kelas'
    ));

})->middleware('webrole:admin');

Route::get('/dashboard/admin/siswa/detail/{id}', function ($id) {
    $user = session('user');
    $data = detailProfilSiswaData((int) $id);
    $layout = 'admin';

    return view('dashboard.siswa.detail', $data + compact('user', 'layout'));
})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| UPDATE SISWA
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/siswa/update/{id}', function (Request $request, $id) {

    $request->validate([
        'nama' => 'required',

        'nis' => 'required', // TAMBAH

        'username' => 'required',
        'kelas_id' => 'required',
    ]);

    $before = User::where('id', $id)->where('role', 'siswa')->first();

    User::where('id', $id)
        ->update([

            'nama' => $request->nama,

            'nis' => $request->nis, // TAMBAH

            'username' => $request->username,

            /*
            |--------------------------------------------------------------------------
            | RELASI KELAS
            |--------------------------------------------------------------------------
            */
            'kelas_id' => $request->kelas_id,

            /*
            |--------------------------------------------------------------------------
            | NO ORANG TUA
            |--------------------------------------------------------------------------
            */
            'no_ortu' => $request->no_ortu,
            'nama_ortu' => $request->nama_ortu,

            'updated_at' => now(),

        ]);
    AuditLogger::record('update', 'users', (int) $id, 'Data siswa diupdate', $before, User::find($id), $request);

    return redirect('/dashboard/admin/siswa')
        ->with('success', 'Data siswa berhasil diupdate');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/delete/{id}', function ($id) {

    $before = User::where('id', $id)->where('role', 'siswa')->first();

    User::where('id', $id)
        ->where('role', 'siswa')
        ->delete();
    AuditLogger::record('delete', 'users', (int) $id, 'Data siswa dihapus', $before, null, request());

    return redirect('/dashboard/admin/siswa')
        ->with('success', 'Data siswa berhasil dihapus');

})->middleware('webrole:admin');
/*

/*
|--------------------------------------------------------------------------
| LIST WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas', function () {

    $user = session('user');

    $wali = DB::table('kelas as k')

        ->leftJoin(
            'users as u',
            'u.id',
            '=',
            'k.wali_kelas_id'
        )

        /*
        |--------------------------------------------------------------------------
        | HITUNG SISWA
        |--------------------------------------------------------------------------
        */
        ->leftJoin(
            'users as s',
            's.kelas_id',
            '=',
            'k.id'
        )

        ->select(

            'k.id',

            'k.nama_kelas',

            'k.wali_kelas_id',

            'u.nama',

            'u.username',

            DB::raw(

                'COUNT(

                    CASE

                    WHEN

                    s.role="siswa"

                    THEN

                    s.id

                    END

                )

                as

                jumlah_siswa'

            )

        )

        ->groupBy(

            'k.id',

            'k.nama_kelas',

            'k.wali_kelas_id',

            'u.nama',

            'u.username'

        )

        ->orderBy(
            'k.nama_kelas'
        )

        ->get();

    return view(

        'dashboard.wali_kelas.index',

        compact(

            'user',

            'wali'

        )

    );

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| FORM TAMBAH / SET WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/create', function () {

    $user = session('user');

    // semua guru
    $guru = User::where('role', 'guru')
        ->orderBy('nama')
        ->get();

    // semua kelas
    $kelas = DB::table('kelas')
        ->orderBy('nama_kelas')
        ->get();

    return view('dashboard.wali_kelas.create', compact(
        'user',
        'guru',
        'kelas'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN WALI KELAS
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/wali-kelas/store', function (Request $request) {

    $request->validate([
        'guru_id' => 'required',
        'kelas_id' => 'required',
    ]);

    /*
    |--------------------------------------------------------------------------
    | CEK GURU SUDAH JADI WALI?
    |--------------------------------------------------------------------------
    */
    $cekGuru = DB::table('kelas')

        ->where(
            'wali_kelas_id',
            $request->guru_id
        )

        ->exists();

    if ($cekGuru) {

        return back()->with(

            'error',

            'Guru sudah menjadi wali kelas di kelas lain'

        );

    }

    /*
    |--------------------------------------------------------------------------
    | CEK KELAS SUDAH ADA WALI?
    |--------------------------------------------------------------------------
    */
    $cekKelas = DB::table('kelas')

        ->where(
            'id',
            $request->kelas_id
        )

        ->whereNotNull(
            'wali_kelas_id'
        )

        ->exists();

    if ($cekKelas) {

        return back()->with(

            'error',

            'Kelas ini sudah mempunyai wali kelas'

        );

    }

    /*
    |--------------------------------------------------------------------------
    | SIMPAN
    |--------------------------------------------------------------------------
    */
    DB::table('kelas')

        ->where(
            'id',
            $request->kelas_id
        )

        ->update([

            'wali_kelas_id' => $request->guru_id,

            'updated_at' => now(),

        ]);

    return redirect('/dashboard/admin/wali-kelas')

        ->with(

            'success',

            'Wali kelas berhasil ditambahkan'

        );

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM EDIT WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/edit/{id}', function ($id) {

    $user = session('user');

    $kelas = DB::table('kelas')
        ->where('id', $id)
        ->first();

    $guru = User::where('role', 'guru')
        ->orderBy('nama')
        ->get();

    return view(

        'dashboard.wali_kelas.edit',

        compact(

            'user',

            'kelas',

            'guru'

        )

    );

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| UPDATE WALI KELAS
|--------------------------------------------------------------------------
*/
Route::post(

    '/dashboard/admin/wali-kelas/update/{id}',

    function (

        Request $request,

        $id

    ) {

        $request->validate([

            'wali_kelas_id' => 'required',

        ]);

        /*
        |--------------------------------------------------------------------------
        | CEK GURU SUDAH JADI WALI?
        |--------------------------------------------------------------------------
        */
        $cek = DB::table('kelas')
            ->where(

                'wali_kelas_id',

                $request->wali_kelas_id

            )
            ->where(

                'id',

                '!=',

                $id

            )
            ->exists();

        if ($cek) {

            return back()
                ->with(

                    'error',

                    'Guru sudah menjadi wali kelas lain'

                );

        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */
        DB::table('kelas')
            ->where(

                'id',

                $id

            )
            ->update([

                'wali_kelas_id' => $request->wali_kelas_id,

                'updated_at' => now(),

            ]);

        return redirect(

            '/dashboard/admin/wali-kelas'

        )
            ->with(

                'success',

                'Wali kelas berhasil diupdate'

            );

    })
    ->middleware(

        'webrole:admin'

    );

/*
|--------------------------------------------------------------------------
| HAPUS WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/delete/{id}', function ($id) {

    DB::table('kelas')

        ->where(
            'id',
            $id
        )

        ->update([

            'wali_kelas_id' => null,

            'updated_at' => now(),

        ]);

    return redirect(

        '/dashboard/admin/wali-kelas'

    )
        ->with(

            'success',

            'Wali kelas berhasil dihapus'

        );

})->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
| DASHBOARD PIKET
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/piket', function (Request $request) {

    $user = session('user');

    $hariSekarang = strtolower(now()->locale('id')->translatedFormat('l'));

    $jadwalPiketHariIni = null;
    $jadwalMenggantikanHariIni = collect();

    if ($user->role === 'guru') {
        $jadwalPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $user->id)
            ->where('hari', $hariSekarang)
            ->where('aktif', 1)
            ->first();

        $jadwalMenggantikanHariIni = DB::table('guru_pikets as gp')
            ->join('users as u', 'u.id', '=', 'gp.guru_id')
            ->where('gp.hari', $hariSekarang)
            ->where('gp.aktif', 1)
            ->whereIn('gp.status', ['Izin', 'Sakit'])
            ->where(function ($query) use ($user) {
                $query->where('gp.guru_pengganti_id', $user->id)
                    ->orWhere('gp.guru_pengganti2_id', $user->id);
            })
            ->select('gp.*', 'u.nama as guru_digantikan')
            ->get();

        if (! $jadwalPiketHariIni && $jadwalMenggantikanHariIni->isEmpty()) {
            abort(403, 'Anda tidak bertugas sebagai guru piket hari ini.');
        }
    }

    $tipe = $request->get('tipe', 'masuk');

    $qr = QrCode::whereDate('tanggal', now()->toDateString())
        ->where('tipe', $tipe)
        ->latest('id')
        ->first();

    $totalSiswa = User::where('role', 'siswa')->count();
    $tanggalFilter = $request->get('tanggal', now()->toDateString());
    $kelasFilter = $request->get('kelas_id');

    $absensiSiswa = DB::table('users as s')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->leftJoin('absensis as a', function ($join) {
            $join->on('a.id_siswa', '=', 's.id')
                ->whereDate('a.tanggal', request()->get('tanggal', now()->toDateString()));
        })
        ->where('s.role', 'siswa')
        ->when($kelasFilter, fn ($query) => $query->where('s.kelas_id', $kelasFilter))
        ->select(
            's.id',
            's.nama',
            's.nis',
            'k.nama_kelas',
            'a.tanggal',
            'a.jam_masuk',
            'a.jam_pulang',
            'a.status_masuk',
            'a.status_pulang'
        )
        ->orderBy('k.nama_kelas')
        ->orderBy('s.nama')
        ->get();

    $riwayatAbsensi = DB::table('absensis as a')
        ->join('users as s', 's.id', '=', 'a.id_siswa')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->when($kelasFilter, fn ($query) => $query->where('s.kelas_id', $kelasFilter))
        ->whereDate('a.tanggal', '>=', now()->subDays(7)->toDateString())
        ->select('a.*', 's.nama', 's.nis', 'k.nama_kelas')
        ->orderByDesc('a.tanggal')
        ->orderBy('k.nama_kelas')
        ->orderBy('s.nama')
        ->get();

    $rekapJadwalPiket = DB::table('guru_pikets as gp')
        ->join('users as g', 'g.id', '=', 'gp.guru_id')
        ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
        ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
        ->select('gp.*', 'g.nama as guru_utama', 'g1.nama as guru_pengganti', 'g2.nama as guru_pengganti2')
        ->orderBy('gp.hari')
        ->orderBy('gp.jam_mulai')
        ->get();

    $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
    $activePiketPage = $request->get('page', 'dashboard');

    return view('dashboard.piket', compact(
        'user',
        'qr',
        'tipe',
        'totalSiswa',
        'absensiSiswa',
        'riwayatAbsensi',
        'rekapJadwalPiket',
        'kelas',
        'kelasFilter',
        'tanggalFilter',
        'activePiketPage',
        'jadwalPiketHariIni',
        'jadwalMenggantikanHariIni'
    ));

})->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/absensi-harian', function (Request $request) {
    return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'absensi'])));
})->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/riwayat-absensi', function (Request $request) {
    return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'riwayat'])));
})->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/rekap-jadwal', function (Request $request) {
    return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'jadwal'])));
})->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/qr-harian', function (Request $request) {
    return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'qr'])));
})->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/absensi/{siswaId}/view', function (Request $request, $siswaId) {
    $user = session('user');
    $tanggal = $request->get('tanggal', now()->toDateString());

    $siswa = User::where('role', 'siswa')->findOrFail($siswaId);
    $kelas = DB::table('kelas')->where('id', $siswa->kelas_id)->first();
    $absensi = DB::table('absensis')
        ->where('id_siswa', $siswa->id)
        ->whereDate('tanggal', $tanggal)
        ->first();

    return view('dashboard.piket_absensi_view', compact('user', 'siswa', 'kelas', 'absensi', 'tanggal'));
})->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/absensi/{siswaId}/edit', function (Request $request, $siswaId) {
    $user = session('user');
    $tanggal = $request->get('tanggal', now()->toDateString());

    $siswa = User::where('role', 'siswa')->findOrFail($siswaId);
    $kelas = DB::table('kelas')->where('id', $siswa->kelas_id)->first();
    $absensi = DB::table('absensis')
        ->where('id_siswa', $siswa->id)
        ->whereDate('tanggal', $tanggal)
        ->first();

    return view('dashboard.piket_absensi_edit', compact('user', 'siswa', 'kelas', 'absensi', 'tanggal'));
})->middleware('webrole:piket,guru');

Route::post('/dashboard/piket/absensi/{siswaId}/update', function (Request $request, $siswaId) {
    $request->validate([
        'tanggal' => 'required|date',
        'jam_masuk' => 'nullable',
        'status_masuk' => 'nullable|string|max:50',
        'jam_pulang' => 'nullable',
        'status_pulang' => 'nullable|string|max:50',
    ]);

    $siswa = User::where('role', 'siswa')->findOrFail($siswaId);
    $statusMasuk = $request->status_masuk ?: null;
    $statusPulang = $request->status_pulang ?: null;

    if (in_array($statusMasuk, ['izin', 'sakit']) && ! $statusPulang) {
        $statusPulang = $statusMasuk;
    }

    $payload = [
        'tahun_ajaran_id' => DB::table('tahun_ajarans')->where('aktif', true)->value('id'),
        'jam_masuk' => $request->jam_masuk ?: null,
        'status_masuk' => $statusMasuk,
        'jam_pulang' => $request->jam_pulang ?: null,
        'status_pulang' => $statusPulang,
        'updated_at' => now(),
    ];

    $existing = DB::table('absensis')
        ->where('id_siswa', $siswa->id)
        ->whereDate('tanggal', $request->tanggal)
        ->first();

    if ($existing) {
        DB::table('absensis')->where('id', $existing->id)->update($payload);
        AuditLogger::record('update', 'absensis', (int) $existing->id, 'Absensi harian diubah guru piket', $existing, DB::table('absensis')->where('id', $existing->id)->first(), $request);
    } else {
        $newId = DB::table('absensis')->insertGetId($payload + [
            'id_siswa' => $siswa->id,
            'tanggal' => $request->tanggal,
            'created_at' => now(),
        ]);
        AuditLogger::record('create', 'absensis', (int) $newId, 'Absensi harian dibuat guru piket', null, DB::table('absensis')->where('id', $newId)->first(), $request);
    }

    return redirect('/dashboard/piket/absensi-harian?tanggal='.$request->tanggal)
        ->with('success', 'Absensi harian siswa berhasil diperbarui.');
})->middleware('webrole:piket,guru');

/*
|--------------------------------------------------------------------------
| GENERATE QR
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/piket/generate-qr', function (Request $request) {

    $user = session('user');

    if ($libur = hariLiburSekolah(now()->toDateString())) {
        return back()->with('error', 'Hari ini libur: '.$libur->judul.'. QR absensi harian tidak bisa dibuat.');
    }

    if ($user->role === 'guru') {
        $bolehPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $user->id)
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->exists();

        $bolehMenggantikanHariIni = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereIn('status', ['Izin', 'Sakit'])
            ->where(function ($query) use ($user) {
                $query->where('guru_pengganti_id', $user->id)
                    ->orWhere('guru_pengganti2_id', $user->id);
            })
            ->exists();

        if (! $bolehPiketHariIni && ! $bolehMenggantikanHariIni) {
            abort(403, 'Anda tidak bertugas sebagai guru piket hari ini.');
        }
    }

    $request->validate([
        'tipe' => 'required|in:masuk,pulang',
    ]);

    QrCode::create([
        'tanggal' => now()->toDateString(),
        'tipe' => $request->tipe,
        'token' => Str::random(12),
        'expires_at' => now()->addMinutes(AttendanceSettingService::masaAktifQr()),
    ]);

    return redirect('/dashboard/piket?tipe='.$request->tipe);

})->middleware('webrole:piket,guru');

Route::post('/dashboard/piket/status', function (Request $request) {

    $user = session('user');

    $request->validate([
        'status' => 'required|in:hadir,izin,sakit',
    ]);

    if ($user->role !== 'guru') {
        return back()->with('error', 'Status kehadiran hanya untuk guru piket.');
    }

    $jadwalPiket = DB::table('guru_pikets')
        ->where('guru_id', $user->id)
        ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
        ->where('aktif', 1)
        ->first();

    if (! $jadwalPiket) {
        return back()->with('error', 'Anda tidak memiliki jadwal guru piket hari ini.');
    }

    if (Schema::hasColumn('guru_pikets', 'status_dipilih_at') && $jadwalPiket->status_dipilih_at) {
        return back()->with('error', 'Status guru piket sudah dipilih dan tidak bisa diubah lagi.');
    }

    $status = [
        'hadir' => 'Sedang Bertugas',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
    ][$request->status];

    $updateGuruPiket = [
        'status' => $status,
        'updated_at' => now(),
    ];

    if (Schema::hasColumn('guru_pikets', 'status_dipilih_at')) {
        $updateGuruPiket['status_dipilih_at'] = now();
    }

    DB::table('guru_pikets')
        ->where('id', $jadwalPiket->id)
        ->update($updateGuruPiket);

    if (in_array($request->status, ['izin', 'sakit'])) {
        $jadwalDialihkan = DB::table('jadwal_pelajarans')
            ->where('guru_id', $user->id)
            ->where('hari', now()->locale('id')->isoFormat('dddd'))
            ->whereNull('status_guru')
            ->whereNotNull('guru_pengganti_id')
            ->update([
                'status_guru' => 'digantikan',
                'alasan_tidak_hadir' => $request->status,
                'updated_at' => now(),
            ]);

        $penggantiPiket = collect([
            $jadwalPiket->guru_pengganti_id,
            $jadwalPiket->guru_pengganti2_id,
        ])->filter()->values();

        $namaPenggantiPiket = User::whereIn('id', $penggantiPiket)
            ->orderBy('nama')
            ->pluck('nama')
            ->implode(', ');

        buatNotifikasi([
            'user_id' => null,
            'judul' => 'Guru Piket Tidak Hadir',
            'pesan' => $user->nama.' '.$request->status.' sebagai guru piket. Pengganti: '.($namaPenggantiPiket ?: '-').'. Jadwal pelajaran dialihkan: '.$jadwalDialihkan,
            'kategori' => 'guru_piket_pengganti',
            'severity' => 'warning',
            'source_type' => 'guru_pikets',
            'source_id' => $jadwalPiket->id,
            'payload' => [
                'guru_utama' => $user->nama,
                'guru_pengganti' => $namaPenggantiPiket ?: '-',
                'alasan' => $request->status,
                'jadwal_dialihkan' => $jadwalDialihkan,
                'hari' => $jadwalPiket->hari,
                'jam' => $jadwalPiket->jam_mulai.' - '.$jadwalPiket->jam_selesai,
            ],
        ]);

        foreach ($penggantiPiket as $penggantiId) {
            buatNotifikasi([
                'user_id' => $penggantiId,
                'judul' => 'Tugas Guru Piket Pengganti',
                'pesan' => 'Anda menggantikan '.$user->nama.' sebagai guru piket karena '.$request->status.'.',
                'kategori' => 'guru_piket_pengganti',
                'severity' => 'warning',
                'source_type' => 'guru_pikets',
                'source_id' => $jadwalPiket->id,
                'payload' => [
                    'guru_utama' => $user->nama,
                    'alasan' => $request->status,
                    'hari' => $jadwalPiket->hari,
                    'jam' => $jadwalPiket->jam_mulai.' - '.$jadwalPiket->jam_selesai,
                ],
            ]);
        }

        return back()->with(
            'success',
            'Status guru piket disimpan. '.$jadwalDialihkan.' jadwal pelajaran hari ini dialihkan ke guru pengganti.'
        );
    }

    return back()->with('success', 'Status hadir guru piket berhasil disimpan dan sudah dikunci.');

})->middleware('webrole:piket,guru');

/*
|--------------------------------------------------------------------------
| DASHBOARD WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali', function () {

    $user = session('user');

    // cek apakah guru ini wali kelas
    $wali = DB::table('kelas')
        ->select('id', 'nama_kelas')
        ->where('wali_kelas_id', $user->id)
        ->first();

    // kalau bukan wali kelas
    if (! $wali) {
        abort(403, 'Akses ditolak');
    }

    $siswa = User::where('role', 'siswa')
        ->where('kelas_id', $wali->id)
        ->orderBy('nama')
        ->get();

    foreach ($siswa as $item) {
        $item->nama_kelas = $wali->nama_kelas;
    }

    foreach ($siswa as $s) {

        $absen = DB::table('absensis')
            ->where('id_siswa', $s->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        if ($absen) {

            $s->status_hari_ini = $absen->status_masuk;

        } else {

            $s->status_hari_ini = 'belum_absen';
        }
    }

    $isGuruMapelHariIni = DB::table('jadwal_pelajarans')
        ->where('hari', now()->locale('id')->isoFormat('dddd'))
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere(function ($pengganti) use ($user) {
                    $pengganti->where('guru_pengganti_id', $user->id)
                        ->where('status_guru', 'digantikan');
                });
        })
        ->exists();

    $isGuruPiketHariIni = DB::table('guru_pikets')
        ->where('guru_id', $user->id)
        ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
        ->where('aktif', 1)
        ->exists();

    $isGuruPiketPenggantiHariIni = DB::table('guru_pikets')
        ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
        ->where('aktif', 1)
        ->whereIn('status', ['Izin', 'Sakit'])
        ->where(function ($query) use ($user) {
            $query->where('guru_pengganti_id', $user->id)
                ->orWhere('guru_pengganti2_id', $user->id);
        })
        ->exists();

    return view('dashboard.wali', compact(
        'user',
        'siswa',
        'wali',
        'isGuruMapelHariIni',
        'isGuruPiketHariIni',
        'isGuruPiketPenggantiHariIni'
    ));

})->middleware('webrole:guru');
/*
|--------------------------------------------------------------------------
| DATA SISWA WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali/siswa', function () {

    $user = session('user');

    $wali = DB::table('kelas')
        ->select('id', 'nama_kelas')
        ->where('wali_kelas_id', $user->id)
        ->first();

    if (! $wali) {
        abort(403);
    }

    $siswa = User::where('role', 'siswa')
        ->where('kelas_id', $wali->id)
        ->orderBy('nama')
        ->get();

    foreach ($siswa as $item) {
        $item->nama_kelas = $wali->nama_kelas;
    }

    return view('dashboard.wali_siswa', compact(
        'user',
        'wali',
        'siswa'
    ));

})->middleware('webrole:guru');

Route::get('/dashboard/wali/siswa/detail/{id}', function ($id) {
    $user = session('user');

    $wali = DB::table('kelas')
        ->select('id', 'nama_kelas')
        ->where('wali_kelas_id', $user->id)
        ->first();

    if (! $wali) {
        abort(403);
    }

    $target = User::where('role', 'siswa')
        ->where('kelas_id', $wali->id)
        ->where('id', $id)
        ->first();

    if (! $target) {
        abort(403);
    }

    $data = detailProfilSiswaData((int) $id);
    $layout = 'wali';

    return view('dashboard.siswa.detail', $data + compact('user', 'layout'));
})->middleware('webrole:guru');

/*
|--------------------------------------------------------------------------
| ABSENSI SISWA WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali/absensi', function () {

    $user = session('user');

    $wali = DB::table('kelas')
        ->select('id', 'nama_kelas')
        ->where('wali_kelas_id', $user->id)
        ->first();

    if (! $wali) {
        abort(403);
    }

    $absensi = DB::table('absensis as a')
        ->join('users as s', 's.id', '=', 'a.id_siswa')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->select(
            'a.*',
            's.nama',
            'k.nama_kelas'
        )
        ->where('s.kelas_id', $wali->id)
        ->whereDate('a.tanggal', now()->toDateString())
        ->latest('a.id')
        ->get();

    return view('dashboard.wali_absensi', compact(
        'user',
        'wali',
        'absensi'
    ));

})->middleware('webrole:guru');
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| DASHBOARD GURU MAPEL
|--------------------------------------------------------------------------
*/

Route::get(

    '/dashboard/guru',

    function (Request $request) {

        $user = session(

            'user'

        );

        $hari = now()
            ->locale(

                'id'

            )
            ->isoFormat(

                'dddd'

            );

        $jadwal = DB::table(

            'jadwal_pelajarans as j'

        )
            ->join(

                'kelas as k',

                'k.id',

                '=',

                'j.kelas_id'

            )
            ->join(

                'mapels as m',

                'm.id',

                '=',

                'j.mapel_id'

            )
            ->leftJoin(

                'users as gp',

                'gp.id',

                '=',

                'j.guru_pengganti_id'

            )

        /*
====================================
GURU UTAMA + GURU PENGGANTI
====================================
*/
            ->where(function (

                $q

            ) use (

                $user

            ) {

                /*
Guru utama
*/

                $q->where(

                    'j.guru_id',

                    $user->id

                );

                /*
Guru pengganti
*/

                $q->orWhere(function (

                    $x

                ) use (

                    $user

                ) {

                    $x
                        ->where(

                            'j.guru_pengganti_id',

                            $user->id

                        )
                        ->where(

                            'j.status_guru',

                            'digantikan'

                        );

                });

            })
            ->where(

                'j.hari',

                $hari

            )
            ->select(

                'j.*',

                'k.nama_kelas',

                'm.nama_mapel',

                'gp.nama as guru_pengganti',

                'j.keterangan',

                'j.status_guru',

                'j.alasan_tidak_hadir',

                /*
Buat cek
guru login
guru utama
atau pengganti
*/

                DB::raw(

                    "

CASE


WHEN

j.guru_id

=

{$user->id}


THEN

'guru_utama'



WHEN

j.guru_pengganti_id

=

{$user->id}


THEN

'guru_pengganti'



END


as

role_mengajar

"

                )

            )
            ->orderBy(

                'j.jam_mulai'

            )
            ->get();

        $semuaJadwalGuru = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->leftJoin('users as gu', 'gu.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere(function ($pengganti) use ($user) {
                        $pengganti->where('j.guru_pengganti_id', $user->id)
                            ->where('j.status_guru', 'digantikan');
                    });
            })
            ->select(
                'j.*',
                'k.nama_kelas',
                'jr.nama_jurusan',
                'm.nama_mapel',
                'gu.nama as guru_utama',
                'gp.nama as guru_pengganti',
                DB::raw("CASE WHEN j.guru_id = {$user->id} THEN 'guru_utama' WHEN j.guru_pengganti_id = {$user->id} THEN 'guru_pengganti' END as role_mengajar")
            )
            ->orderBy('j.hari')
            ->orderBy('j.jam_mulai')
            ->get();

        $kelasAjarIds = $jadwal
            ->pluck('kelas_id')
            ->filter()
            ->unique()
            ->values();

        $absensiKelasAjar = collect();
        $absensiMapelKelasAjar = collect();
        $riwayatAbsensiKelasAjar = collect();
        $rekapSiswaGuru = collect();
        $rekapAbsensiMapelGuru = collect();
        $tanggalFilter = $request->get('tanggal', now()->toDateString());
        $liburTanggalFilter = hariLiburSekolah($tanggalFilter);
        $hariFilter = $request->get('hari');
        $bulanFilter = $request->get('bulan');
        $tahunFilter = $request->get('tahun');
        $kelasFilter = $request->get('kelas_id');
        $jurusanFilter = $request->get('jurusan_id');
        $statusHarianFilter = $request->get('status_harian');
        $kelasAjar = collect();
        $jurusan = DB::table('jurusan')->orderBy('nama_jurusan')->get();

        $semuaKelasAjarIds = $semuaJadwalGuru
            ->pluck('kelas_id')
            ->filter()
            ->unique()
            ->values();

        if ($kelasAjarIds->isNotEmpty()) {
            $kelasAjar = DB::table('kelas')
                ->whereIn('id', $kelasAjarIds)
                ->orderBy('nama_kelas')
                ->get();

            $filteredKelasIds = $kelasAjarIds;

            if ($kelasFilter) {
                $filteredKelasIds = $filteredKelasIds
                    ->filter(fn ($id) => (string) $id === (string) $kelasFilter)
                    ->values();
            }

            if ($jurusanFilter) {
                $kelasJurusanIds = DB::table('kelas')
                    ->where('jurusan_id', $jurusanFilter)
                    ->pluck('id');

                $filteredKelasIds = $filteredKelasIds
                    ->intersect($kelasJurusanIds)
                    ->values();
            }

            $absensiKelasAjar = DB::table('users as s')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->leftJoin('absensis as a', function ($join) {
                    $join->on('a.id_siswa', '=', 's.id')
                        ->whereDate('a.tanggal', request()->get('tanggal', now()->toDateString()));
                })
                ->where('s.role', 'siswa')
                ->whereIn('s.kelas_id', $filteredKelasIds)
                ->select(
                    'a.id as absensi_id',
                    's.id',
                    's.nama',
                    's.nis',
                    'k.nama_kelas',
                    'jr.nama_jurusan',
                    'a.tanggal',
                    'a.jam_masuk',
                    'a.jam_pulang',
                    'a.status_masuk',
                    'a.status_pulang'
                )
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->get();

            $absensiMapelKelasAjar = DB::table('jadwal_pelajarans as j')
                ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->join('users as s', function ($join) {
                    $join->on('s.kelas_id', '=', 'j.kelas_id')
                        ->where('s.role', 'siswa');
                })
                ->leftJoin('absensi_mapels as am', function ($join) use ($tanggalFilter) {
                    $join->on('am.jadwal_id', '=', 'j.id')
                        ->on('am.siswa_id', '=', 's.id')
                        ->whereDate('am.tanggal', $tanggalFilter);
                })
                ->leftJoin('absensis as ah', function ($join) use ($tanggalFilter) {
                    $join->on('ah.id_siswa', '=', 's.id')
                        ->whereDate('ah.tanggal', $tanggalFilter);
                })
                ->whereIn('j.id', $jadwal->pluck('id'))
                ->whereIn('j.kelas_id', $filteredKelasIds)
                ->when($statusHarianFilter === 'izin', function ($query) {
                    $query->where(function ($where) {
                        $where->where('ah.status_masuk', 'izin')
                            ->orWhere('ah.status_pulang', 'izin');
                    });
                })
                ->when($statusHarianFilter === 'sakit', function ($query) {
                    $query->where(function ($where) {
                        $where->where('ah.status_masuk', 'sakit')
                            ->orWhere('ah.status_pulang', 'sakit');
                    });
                })
                ->when($statusHarianFilter === 'alfa' && ! $liburTanggalFilter, function ($query) {
                    $query->where(function ($where) {
                        $where->whereNull('ah.id')
                            ->orWhere('ah.status_masuk', 'alfa')
                            ->orWhere('ah.status_masuk', 'alpa')
                            ->orWhere('ah.status_pulang', 'alfa')
                            ->orWhere('ah.status_pulang', 'alpa');
                    });
                })
                ->when($statusHarianFilter === 'alfa' && $liburTanggalFilter, function ($query) {
                    $query->whereRaw('1 = 0');
                })
                ->when($statusHarianFilter === 'hadir', function ($query) {
                    $query->whereNotNull('ah.jam_masuk')
                        ->whereNotIn(DB::raw('COALESCE(ah.status_masuk, "")'), ['izin', 'sakit', 'alfa', 'alpa'])
                        ->whereNotIn(DB::raw('COALESCE(ah.status_pulang, "")'), ['izin', 'sakit', 'alfa', 'alpa']);
                })
                ->select(
                    'am.id as absensi_mapel_id',
                    'am.tanggal',
                    'am.jam_scan',
                    'am.status',
                    'ah.jam_masuk as jam_harian_masuk',
                    'ah.status_masuk as status_harian_masuk',
                    'ah.jam_pulang as jam_harian_pulang',
                    'ah.status_pulang as status_harian_pulang',
                    'j.id as jadwal_id',
                    'j.status_guru',
                    'j.jam_mulai',
                    'j.jam_selesai',
                    's.id as siswa_id',
                    's.nama',
                    's.nis',
                    'k.nama_kelas',
                    'jr.nama_jurusan',
                    'm.nama_mapel',
                    DB::raw("CASE WHEN j.status_guru = 'digantikan' AND j.guru_pengganti_id = {$user->id} THEN 1 WHEN (j.status_guru IS NULL OR j.status_guru = 'normal') AND j.guru_id = {$user->id} THEN 1 ELSE 0 END as boleh_kelola_mapel")
                )
                ->orderBy('k.nama_kelas')
                ->orderBy('m.nama_mapel')
                ->orderBy('s.nama')
                ->get();

            if ($liburTanggalFilter) {
                $absensiMapelKelasAjar = $absensiMapelKelasAjar->map(function ($row) use ($liburTanggalFilter) {
                    $row->status_harian_masuk = $row->status_harian_masuk ?: 'libur';
                    $row->status_harian_pulang = $row->status_harian_pulang ?: 'libur';
                    $row->keterangan_libur = $liburTanggalFilter->judul;
                    return $row;
                });
            }

            $riwayatAbsensiKelasAjar = DB::table('absensis as a')
                ->join('users as s', 's.id', '=', 'a.id_siswa')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->whereIn('s.kelas_id', $filteredKelasIds)
                ->when($hariFilter, function ($query) use ($hariFilter) {
                    $dayIndex = [
                        'Minggu' => 0,
                        'Senin' => 1,
                        'Selasa' => 2,
                        'Rabu' => 3,
                        'Kamis' => 4,
                        'Jumat' => 5,
                        'Sabtu' => 6,
                    ][$hariFilter] ?? null;

                    if ($dayIndex !== null) {
                        $query->whereRaw('DAYOFWEEK(a.tanggal) = ?', [$dayIndex + 1]);
                    }
                })
                ->when($bulanFilter, fn ($query) => $query->whereMonth('a.tanggal', $bulanFilter))
                ->when($tahunFilter, fn ($query) => $query->whereYear('a.tanggal', $tahunFilter))
                ->when(! $bulanFilter && ! $tahunFilter && ! $hariFilter, fn ($query) => $query->whereDate('a.tanggal', '>=', now()->subDays(7)->toDateString()))
                ->select(
                    'a.*',
                    's.nama',
                    's.nis',
                    'k.nama_kelas',
                    'jr.nama_jurusan'
                )
                ->orderByDesc('a.tanggal')
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->limit(100)
                ->get();
        }

        if ($semuaKelasAjarIds->isNotEmpty()) {
            $rekapSiswaGuru = DB::table('users as s')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->where('s.role', 'siswa')
                ->whereIn('s.kelas_id', $semuaKelasAjarIds)
                ->select('s.*', 'k.nama_kelas', 'jr.nama_jurusan')
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->get();

            $rekapAbsensiMapelGuru = DB::table('absensi_mapels as a')
                ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
                ->join('users as s', 's.id', '=', 'a.siswa_id')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->where(function ($query) use ($user) {
                    $query->where('j.guru_id', $user->id)
                        ->orWhere(function ($pengganti) use ($user) {
                            $pengganti->where('j.guru_pengganti_id', $user->id)
                                ->where('j.status_guru', 'digantikan');
                        });
                })
                ->select('a.*', 's.nama as nama_siswa', 'k.nama_kelas', 'm.nama_mapel', 'j.hari', 'j.jam_mulai', 'j.jam_selesai')
                ->latest('a.tanggal')
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->limit(150)
                ->get();
        }

        $isWaliKelas = DB::table(

            'kelas'

        )
            ->where(

                'wali_kelas_id',

                $user->id

            )
            ->exists();

        $isGuruPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $user->id)
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->exists();

        $isGuruPiketPenggantiHariIni = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereIn('status', ['Izin', 'Sakit'])
            ->where(function ($query) use ($user) {
                $query->where('guru_pengganti_id', $user->id)
                    ->orWhere('guru_pengganti2_id', $user->id);
            })
            ->exists();

        return view(

            'dashboard.guru',

            compact(

                'user',

                'jadwal',

                'hari',

                'isWaliKelas',

                'isGuruPiketHariIni',

                'isGuruPiketPenggantiHariIni',

                'absensiKelasAjar',

                'absensiMapelKelasAjar',

                'riwayatAbsensiKelasAjar'

                ,
                'semuaJadwalGuru',

                'rekapSiswaGuru',

                'rekapAbsensiMapelGuru',

                'kelasAjar',

                'jurusan',

                'tanggalFilter',

                'hariFilter',

                'bulanFilter',

                'tahunFilter',

                'kelasFilter',

                'jurusanFilter'

                ,
                'statusHarianFilter'

            ) + ['activeGuruPage' => $request->get('page', 'dashboard')]

        );

    })
    ->middleware(

        'webrole:guru'

    );

Route::get('/dashboard/guru/jadwal', function (Request $request) {
    return redirect('/dashboard/guru?page=jadwal');
})->middleware('webrole:guru');

Route::get('/dashboard/guru/verifikasi-absensi', function (Request $request) {
    return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'verifikasi'])));
})->middleware('webrole:guru');

Route::get('/dashboard/guru/riwayat-absensi', function (Request $request) {
    return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'riwayat'])));
})->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-siswa', function (Request $request) {
    return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_siswa'])));
})->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-absensi', function (Request $request) {
    return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_absensi'])));
})->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-absensi-mapel', function (Request $request) {
    return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_absensi_mapel'])));
})->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-jadwal', function (Request $request) {
    return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_jadwal'])));
})->middleware('webrole:guru');

Route::get('/dashboard/guru/absensi/{siswaId}/view', function (Request $request, $siswaId) {
    $user = session('user');
    $tanggal = $request->get('tanggal', now()->toDateString());

    $siswa = User::where('role', 'siswa')->findOrFail($siswaId);

    $bolehAkses = DB::table('jadwal_pelajarans')
        ->where('kelas_id', $siswa->kelas_id)
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere(function ($pengganti) use ($user) {
                    $pengganti->where('guru_pengganti_id', $user->id)
                        ->where('status_guru', 'digantikan');
                });
        })
        ->exists();

    if (! $bolehAkses) {
        abort(403);
    }

    $absensi = DB::table('absensis')
        ->where('id_siswa', $siswa->id)
        ->whereDate('tanggal', $tanggal)
        ->first();

    $kelas = DB::table('kelas')->where('id', $siswa->kelas_id)->first();
    $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
    $isGuruPiketHariIni = DB::table('guru_pikets')
        ->where('guru_id', $user->id)
        ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
        ->where('aktif', 1)
        ->exists();
    $isGuruPiketPenggantiHariIni = DB::table('guru_pikets')
        ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
        ->where('aktif', 1)
        ->whereIn('status', ['Izin', 'Sakit'])
        ->where(function ($query) use ($user) {
            $query->where('guru_pengganti_id', $user->id)
                ->orWhere('guru_pengganti2_id', $user->id);
        })
        ->exists();

    return view('dashboard.guru_absensi_view', compact(
        'user',
        'siswa',
        'kelas',
        'absensi',
        'tanggal',
        'isWaliKelas',
        'isGuruPiketHariIni',
        'isGuruPiketPenggantiHariIni'
    ));
})->middleware('webrole:guru');

Route::get('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/view', function (Request $request, $jadwalId, $siswaId) {
    $user = session('user');
    $tanggal = $request->get('tanggal', now()->toDateString());

    $jadwal = DB::table('jadwal_pelajarans as j')
        ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
        ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
        ->where('j.id', $jadwalId)
        ->where(function ($query) use ($user) {
            $query->where(function ($utama) use ($user) {
                $utama->where('j.guru_id', $user->id)
                    ->where(function ($status) {
                        $status->whereNull('j.status_guru')
                            ->orWhere('j.status_guru', 'normal');
                    });
            })->orWhere(function ($pengganti) use ($user) {
                $pengganti->where('j.guru_pengganti_id', $user->id)
                    ->where('j.status_guru', 'digantikan');
            });
        })
        ->select('j.*', 'k.nama_kelas', 'm.nama_mapel')
        ->first();

    if (! $jadwal) {
        abort(403);
    }

    $siswa = User::where('role', 'siswa')
        ->where('kelas_id', $jadwal->kelas_id)
        ->findOrFail($siswaId);

    $absensiMapel = DB::table('absensi_mapels')
        ->where('jadwal_id', $jadwalId)
        ->where('siswa_id', $siswaId)
        ->whereDate('tanggal', $tanggal)
        ->first();

    $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
    $isGuruPiketHariIni = DB::table('guru_pikets')->where('guru_id', $user->id)->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->exists();
    $isGuruPiketPenggantiHariIni = DB::table('guru_pikets')->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->whereIn('status', ['Izin', 'Sakit'])->where(function ($query) use ($user) {
        $query->where('guru_pengganti_id', $user->id)->orWhere('guru_pengganti2_id', $user->id);
    })->exists();

    return view('dashboard.guru_absensi_mapel_view', compact('user', 'jadwal', 'siswa', 'absensiMapel', 'tanggal', 'isWaliKelas', 'isGuruPiketHariIni', 'isGuruPiketPenggantiHariIni'));
})->middleware('webrole:guru');

Route::get('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/edit', function (Request $request, $jadwalId, $siswaId) {
    $user = session('user');
    $tanggal = $request->get('tanggal', now()->toDateString());

    $jadwal = DB::table('jadwal_pelajarans as j')
        ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
        ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
        ->where('j.id', $jadwalId)
        ->where(function ($query) use ($user) {
            $query->where('j.guru_id', $user->id)
                ->orWhere(function ($pengganti) use ($user) {
                    $pengganti->where('j.guru_pengganti_id', $user->id)
                        ->where('j.status_guru', 'digantikan');
                });
        })
        ->select('j.*', 'k.nama_kelas', 'm.nama_mapel')
        ->first();

    if (! $jadwal) {
        abort(403);
    }

    $siswa = User::where('role', 'siswa')->where('kelas_id', $jadwal->kelas_id)->findOrFail($siswaId);
    $absensiMapel = DB::table('absensi_mapels')->where('jadwal_id', $jadwalId)->where('siswa_id', $siswaId)->whereDate('tanggal', $tanggal)->first();
    $absensiHarian = DB::table('absensis')
        ->where('id_siswa', $siswaId)
        ->whereDate('tanggal', $tanggal)
        ->first();

    $statusHarian = $absensiHarian
        ? (in_array($absensiHarian->status_masuk, ['izin', 'sakit', 'alfa', 'alpa'])
            ? $absensiHarian->status_masuk
            : (in_array($absensiHarian->status_pulang, ['izin', 'sakit', 'alfa', 'alpa']) ? $absensiHarian->status_pulang : null))
        : AttendanceSettingService::statusDefaultAlfa();

    if ($statusHarian || ! $absensiHarian?->jam_masuk) {
        return redirect('/dashboard/guru/absensi-mapel/'.$jadwalId.'/'.$siswaId.'/view?tanggal='.$tanggal)
            ->with('error', 'Siswa tidak hadir pada absensi harian guru piket, absen mapel tidak bisa diedit.');
    }

    $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
    $isGuruPiketHariIni = DB::table('guru_pikets')->where('guru_id', $user->id)->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->exists();
    $isGuruPiketPenggantiHariIni = DB::table('guru_pikets')->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->whereIn('status', ['Izin', 'Sakit'])->where(function ($query) use ($user) {
        $query->where('guru_pengganti_id', $user->id)->orWhere('guru_pengganti2_id', $user->id);
    })->exists();

    return view('dashboard.guru_absensi_mapel_edit', compact('user', 'jadwal', 'siswa', 'absensiMapel', 'tanggal', 'isWaliKelas', 'isGuruPiketHariIni', 'isGuruPiketPenggantiHariIni'));
})->middleware('webrole:guru');

Route::post('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/update', function (Request $request, $jadwalId, $siswaId) {
    $user = session('user');
    $request->validate([
        'tanggal' => 'required|date',
        'jam_scan' => 'nullable',
        'status' => 'required|string|max:50',
    ]);

    $jadwal = DB::table('jadwal_pelajarans')
        ->where('id', $jadwalId)
        ->where(function ($query) use ($user) {
            $query->where(function ($utama) use ($user) {
                $utama->where('guru_id', $user->id)
                    ->where(function ($status) {
                        $status->whereNull('status_guru')
                            ->orWhere('status_guru', 'normal');
                    });
            })->orWhere(function ($pengganti) use ($user) {
                $pengganti->where('guru_pengganti_id', $user->id)
                    ->where('status_guru', 'digantikan');
            });
        })
        ->first();

    if (! $jadwal) {
        abort(403);
    }

    User::where('role', 'siswa')->where('kelas_id', $jadwal->kelas_id)->findOrFail($siswaId);

    $absensiHarian = DB::table('absensis')
        ->where('id_siswa', $siswaId)
        ->whereDate('tanggal', $request->tanggal)
        ->first();

    $statusHarian = $absensiHarian
        ? (in_array($absensiHarian->status_masuk, ['izin', 'sakit', 'alfa', 'alpa'])
            ? $absensiHarian->status_masuk
            : (in_array($absensiHarian->status_pulang, ['izin', 'sakit', 'alfa', 'alpa']) ? $absensiHarian->status_pulang : null))
        : AttendanceSettingService::statusDefaultAlfa();

    if ($statusHarian || ! $absensiHarian?->jam_masuk) {
        return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
            ->with('error', 'Siswa tidak hadir pada absensi harian guru piket, absen mapel tidak bisa diedit.');
    }

    $existing = DB::table('absensi_mapels')->where('jadwal_id', $jadwalId)->where('siswa_id', $siswaId)->whereDate('tanggal', $request->tanggal)->first();
    $payload = [
        'tahun_ajaran_id' => DB::table('tahun_ajarans')->where('aktif', true)->value('id'),
        'jam_scan' => $request->jam_scan ?: null,
        'status' => $request->status,
        'updated_at' => now(),
    ];

    if ($existing) {
        DB::table('absensi_mapels')->where('id', $existing->id)->update($payload);
        AuditLogger::record('update', 'absensi_mapels', (int) $existing->id, 'Absensi mapel diubah guru', $existing, DB::table('absensi_mapels')->where('id', $existing->id)->first(), $request);
    } else {
        $newId = DB::table('absensi_mapels')->insertGetId($payload + [
            'jadwal_id' => $jadwalId,
            'siswa_id' => $siswaId,
            'tanggal' => $request->tanggal,
            'created_at' => now(),
        ]);
        AuditLogger::record('create', 'absensi_mapels', (int) $newId, 'Absensi mapel dibuat guru', null, DB::table('absensi_mapels')->where('id', $newId)->first(), $request);
    }

    return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
        ->with('success', 'Absen mapel siswa berhasil diperbarui.');
})->middleware('webrole:guru');

Route::get('/dashboard/guru/absensi/{siswaId}/edit', function (Request $request, $siswaId) {
    $user = session('user');
    $tanggal = $request->get('tanggal', now()->toDateString());

    $siswa = User::where('role', 'siswa')->findOrFail($siswaId);

    $bolehAkses = DB::table('jadwal_pelajarans')
        ->where('kelas_id', $siswa->kelas_id)
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere(function ($pengganti) use ($user) {
                    $pengganti->where('guru_pengganti_id', $user->id)
                        ->where('status_guru', 'digantikan');
                });
        })
        ->exists();

    if (! $bolehAkses) {
        abort(403);
    }

    $absensi = DB::table('absensis')
        ->where('id_siswa', $siswa->id)
        ->whereDate('tanggal', $tanggal)
        ->first();

    $kelas = DB::table('kelas')->where('id', $siswa->kelas_id)->first();
    $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
    $isGuruPiketHariIni = DB::table('guru_pikets')
        ->where('guru_id', $user->id)
        ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
        ->where('aktif', 1)
        ->exists();
    $isGuruPiketPenggantiHariIni = DB::table('guru_pikets')
        ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
        ->where('aktif', 1)
        ->whereIn('status', ['Izin', 'Sakit'])
        ->where(function ($query) use ($user) {
            $query->where('guru_pengganti_id', $user->id)
                ->orWhere('guru_pengganti2_id', $user->id);
        })
        ->exists();

    return view('dashboard.guru_absensi_edit', compact(
        'user',
        'siswa',
        'kelas',
        'absensi',
        'tanggal',
        'isWaliKelas',
        'isGuruPiketHariIni',
        'isGuruPiketPenggantiHariIni'
    ));
})->middleware('webrole:guru');

Route::post('/dashboard/guru/absensi/{siswaId}/update', function (Request $request, $siswaId) {
    $user = session('user');

    $request->validate([
        'tanggal' => 'required|date',
        'jam_masuk' => 'nullable',
        'jam_pulang' => 'nullable',
        'status_masuk' => 'nullable|string|max:50',
        'status_pulang' => 'nullable|string|max:50',
    ]);

    $siswa = User::where('role', 'siswa')->findOrFail($siswaId);

    $bolehAkses = DB::table('jadwal_pelajarans')
        ->where('kelas_id', $siswa->kelas_id)
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere(function ($pengganti) use ($user) {
                    $pengganti->where('guru_pengganti_id', $user->id)
                        ->where('status_guru', 'digantikan');
                });
        })
        ->exists();

    if (! $bolehAkses) {
        abort(403);
    }

    $statusMasuk = $request->status_masuk ?: null;
    $statusPulang = $request->status_pulang ?: null;

    if (in_array($statusMasuk, ['izin', 'sakit']) && ! $statusPulang) {
        $statusPulang = $statusMasuk;
    }

    $existing = DB::table('absensis')
        ->where('id_siswa', $siswa->id)
        ->whereDate('tanggal', $request->tanggal)
        ->first();

    $payload = [
        'tahun_ajaran_id' => DB::table('tahun_ajarans')->where('aktif', true)->value('id'),
        'jam_masuk' => $request->jam_masuk ?: null,
        'jam_pulang' => $request->jam_pulang ?: null,
        'status_masuk' => $statusMasuk,
        'status_pulang' => $statusPulang,
        'updated_at' => now(),
    ];

    if ($existing) {
        DB::table('absensis')->where('id', $existing->id)->update($payload);
        AuditLogger::record('update', 'absensis', (int) $existing->id, 'Absensi harian diubah guru mapel', $existing, DB::table('absensis')->where('id', $existing->id)->first(), $request);
    } else {
        $newId = DB::table('absensis')->insertGetId($payload + [
            'id_siswa' => $siswa->id,
            'tanggal' => $request->tanggal,
            'created_at' => now(),
        ]);
        AuditLogger::record('create', 'absensis', (int) $newId, 'Absensi harian dibuat guru mapel', null, DB::table('absensis')->where('id', $newId)->first(), $request);
    }

    $kelasSiswa = DB::table('kelas')
        ->where('id', $siswa->kelas_id)
        ->first();

    if (in_array($statusMasuk, ['izin', 'sakit']) || in_array($statusPulang, ['izin', 'sakit'])) {
        buatNotifikasi([
            'user_id' => null,
            'judul' => 'Absensi Siswa Diubah Guru Mapel',
            'pesan' => $user->nama.' mengubah absensi '.$siswa->nama.' kelas '.($kelasSiswa->nama_kelas ?? '-').' tanggal '.$request->tanggal.'. Status masuk: '.($statusMasuk ?: '-').', status pulang: '.($statusPulang ?: '-').'.',
            'status' => 'belum_dibaca',
            'kategori' => 'absensi_siswa_diubah',
            'severity' => 'info',
            'source_type' => 'absensis',
            'source_id' => $existing->id ?? ($newId ?? null),
            'payload' => [
                'guru_mapel' => $user->nama,
                'siswa' => $siswa->nama,
                'kelas' => $kelasSiswa->nama_kelas ?? '-',
                'tanggal' => $request->tanggal,
                'status_masuk' => $statusMasuk ?: '-',
                'status_pulang' => $statusPulang ?: '-',
            ],
        ]);
    }

    return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
        ->with('success', 'Absensi siswa berhasil diperbarui.');
})->middleware('webrole:guru');
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| UPDATE STATUS GURU
|--------------------------------------------------------------------------
*/

Route::post(

    '/dashboard/guru/status/{id}',

    function (

        Request $request,

        $id

    ) {

        $user = session('user');

        $request->validate([

            'status' => 'required|in:normal,izin,sakit,inval',

        ]);

        $status = $request->status;

        $jadwal = DB::table(

            'jadwal_pelajarans'

        )
            ->where(

                'id',

                $id

            )
            ->where(

                'guru_id',

                $user->id

            )
            ->first();

        if (! $jadwal) {

            return back()
                ->with(

                    'error',

                    'Jadwal tidak ditemukan'

                );

        }

        /*
        ====================
        SUDAH PILIH STATUS?
        ====================
        */

        if (

            $jadwal->status_guru

            !==

            null

        ) {

            return back()
                ->with(

                    'error',

                    'Status sudah dipilih'

                );

        }

        /*
        ====================
        HADIR
        ====================
        */

        if (

            $status

            ==

            'normal'

        ) {

            DB::table(

                'jadwal_pelajarans'

            )
                ->where(

                    'id',

                    $id

                )
                ->update([

                    'status_guru' => 'normal',

                    'alasan_tidak_hadir' => null,

                    'updated_at' => now(),

                ]);

            return back()
                ->with(

                    'success',

                    'Status hadir disimpan'

                );

        }

        /*
        ====================
        IZIN / SAKIT / INVAL
        ====================
        */

        DB::table(

            'jadwal_pelajarans'

        )
            ->where(

                'id',

                $id

            )
            ->update([

                'status_guru' => 'digantikan',

                'alasan_tidak_hadir' => $status,

                'updated_at' => now(),

            ]);

        /*
        ====================
        AMBIL DATA JADWAL
        ====================
        */

        $data = DB::table(

            'jadwal_pelajarans as j'

        )
            ->join(

                'kelas as k',

                'k.id',

                '=',
                'j.kelas_id'

            )
            ->leftJoin(

                'users as gp',

                'gp.id',

                '=',
                'j.guru_pengganti_id'

            )
            ->where(

                'j.id',

                $id

            )
            ->select(

                'k.nama_kelas',

                'j.jam_mulai',

                'j.jam_selesai',

                'gp.nama as guru_pengganti',

                'j.guru_pengganti_id'

            )
            ->first();

        /*
        ====================
        NOTIF ADMIN
        ====================
        */

        buatNotifikasi([
            'user_id' => null,
            'judul' => 'Guru Tidak Hadir',
            'pesan' => $user->nama.' '.$status.' digantikan '.$data->guru_pengganti.' | '.$data->nama_kelas.' | '.$data->jam_mulai.'-'.$data->jam_selesai,
            'kategori' => 'guru_tidak_hadir',
            'severity' => 'warning',
            'source_type' => 'jadwal_pelajarans',
            'source_id' => $id,
            'payload' => [
                'guru_utama' => $user->nama,
                'guru_pengganti' => $data->guru_pengganti ?: '-',
                'alasan' => $status,
                'kelas' => $data->nama_kelas,
                'jam' => $data->jam_mulai.' - '.$data->jam_selesai,
            ],
        ]);

        /*
        ====================
        NOTIF GURU PENGGANTI
        ====================
        */
        if (

            $data->guru_pengganti_id

        ) {

            buatNotifikasi([
                'user_id' => $data->guru_pengganti_id,
                'judul' => 'Jadwal Digantikan',
                'pesan' => 'Anda menggantikan '.$user->nama.' kelas '.$data->nama_kelas.' '.$data->jam_mulai.'-'.$data->jam_selesai,
                'kategori' => 'jadwal_digantikan',
                'severity' => 'info',
                'source_type' => 'jadwal_pelajarans',
                'source_id' => $id,
                'payload' => [
                    'guru_utama' => $user->nama,
                    'kelas' => $data->nama_kelas,
                    'jam' => $data->jam_mulai.' - '.$data->jam_selesai,
                ],
            ]);

        }

        return back()
            ->with(

                'success',

                'Status berhasil diperbarui'

            );

    })
    ->middleware(

        'webrole:guru'

    );
/*
|--------------------------------------------------------------------------
| MULAI SESI GURU (GENERATE QR)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/guru/mulai-sesi/{jadwalId}', function ($jadwalId) {

    $user = session('user');

    if ($libur = hariLiburSekolah(now()->toDateString())) {
        return back()->with('error', 'Hari ini libur: '.$libur->judul.'. Sesi absen mapel tidak bisa dimulai.');
    }

    $jadwal = DB::table('jadwal_pelajarans')
        ->where('id', $jadwalId)
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere(function ($pengganti) use ($user) {
                    $pengganti->where('guru_pengganti_id', $user->id)
                        ->where('status_guru', 'digantikan');
                });
        })
        ->first();

    if (! $jadwal) {
        abort(403);
    }

    $qr = DB::table('qr_sesis')
        ->where('jadwal_id', $jadwalId)
        ->whereDate('tanggal', now()->toDateString())
        ->where('aktif', 1)
        ->first();

    if (! $qr) {
        $token = Str::random(20);

        DB::table('qr_sesis')->insert([
            'jadwal_id' => $jadwalId,
            'tanggal' => now()->toDateString(),
            'token' => $token,
            'aktif' => 1,
            'expires_at' => now()->addMinutes(AttendanceSettingService::masaAktifQr()),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $qr = DB::table('qr_sesis')
            ->where('jadwal_id', $jadwalId)
            ->whereDate('tanggal', now()->toDateString())
            ->first();
    }

    $detail = DB::table('jadwal_pelajarans as j')
        ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
        ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
        ->select(
            'j.*',
            'k.nama_kelas',
            'm.nama_mapel'
        )
        ->where('j.id', $jadwalId)
        ->first();

    return view('dashboard.guru_qr', compact(
        'user',
        'qr',
        'detail'
    ));

})->middleware('webrole:guru');

/*
|--------------------------------------------------------------------------
| DASHBOARD USERS (SISWA)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/users', function () {

    $user = session('user');

    $siswa = User::where('role', 'siswa')->get();

    return view('dashboard.users', compact('user', 'siswa'));

})->middleware('webrole:siswa');

/*
|--------------------------------------------------------------------------
| WEB MANUAL ABSENSI (TESTING ONLY)
|--------------------------------------------------------------------------
*/
Route::post('/absensi/manual', function (Request $request) {

    $user = session('user');

    if (! $user) {
        return back()->with('error', 'User tidak login');
    }

    $qr = QrCode::where('token', $request->token)->first();

    if (! $qr) {
        return back()->with('error', 'QR tidak valid');
    }

    $request->attributes->set('user_login', $user);

    return app(AbsensiController::class)->scan($request);

});

/*
|--------------------------------------------------------------------------
| LIST GURU PIKET
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru-piket', function (Request $request) {

    $user = session('user');

    $hari = $request->hari;

    $query = DB::table('guru_pikets as gp')
        ->join('users as u', 'u.id', '=', 'gp.guru_id')
        ->leftJoin(
            'users as g2',
            'g2.id',
            '=',
            'gp.guru_pengganti_id'
        )
        ->leftJoin(
            'users as g3',
            'g3.id',
            '=',
            'gp.guru_pengganti2_id'
        )
        ->select(
            'gp.*',
            'u.nama',
            'g2.nama as guru_pengganti',
            'g3.nama as guru_pengganti2'
        );

    /*
    |--------------------------------------------------------------------------
    | FILTER HARI
    |--------------------------------------------------------------------------
    */
    if (! empty($hari)) {

        $query->where(
            'gp.hari',
            strtolower($hari)
        );

    }

    $guruPiket = $query
        ->orderBy('gp.hari')
        ->orderBy('u.nama')
        ->get();

    /*
    |--------------------------------------------------------------------------
    | STATUS OTOMATIS
    |--------------------------------------------------------------------------
    */
    $hariSekarang = strtolower(
        now()
            ->locale('id')
            ->translatedFormat('l')
    );

    $jamSekarang = now()
        ->format('H:i:s');

    foreach ($guruPiket as $g) {

        if (

            $g->hari == $hariSekarang

            &&

            ! in_array(

                $g->status,

                [

                    'Izin',

                    'Sakit',

                    'Digantikan',

                ]

            )

        ) {

            if (

                $jamSekarang < $g->jam_mulai

            ) {

                $status = 'Akan Bertugas';

            } elseif (

                $jamSekarang >= $g->jam_mulai

                &&

                $jamSekarang <= $g->jam_selesai

            ) {

                $status = 'Sedang Bertugas';

            } else {

                $status = 'Selesai';

            }

            DB::table('guru_pikets')

                ->where(
                    'id',
                    $g->id
                )

                ->update([

                    'status' => $status,

                    'updated_at' => now(),

                ]);

            $g->status = $status;

        } elseif (

            $g->hari != $hariSekarang

            &&

            ! in_array(

                $g->status,

                [

                    'Izin',

                    'Sakit',

                    'Digantikan',

                ]

            )

        ) {

            DB::table('guru_pikets')

                ->where(
                    'id',
                    $g->id
                )

                ->update([

                    'status' => 'Akan Bertugas',

                    'updated_at' => now(),

                ]);

            $g->status =
                'Akan Bertugas';

        }

    }

    return view(

        'dashboard.guru_piket.index',

        compact(

            'user',

            'guruPiket',

            'hari'

        )

    );

})
    ->middleware(
        'webrole:admin'
    );

/*
|--------------------------------------------------------------------------
| FORM TAMBAH GURU PIKET
|--------------------------------------------------------------------------
*/
Route::get(

    '/dashboard/admin/guru-piket/create',

    function () {

        $user = session('user');

        $guru = User::where(
            'role',
            'guru'
        )
            ->orderBy(
                'nama'
            )
            ->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

        return view(

            'dashboard.guru_piket.create',

            compact(

                'user',

                'guru'
                ,
                'tahunAjaran'
                ,
                'tahunAjaranAktif'

            )

        );

    })
    ->middleware(
        'webrole:admin'
    );

Route::get('/dashboard/admin/guru-piket/edit/{id}', function ($id) {

    $user = session('user');

    $guruPiket = DB::table('guru_pikets')
        ->where('id', $id)
        ->first();

    if (! $guruPiket) {
        abort(404);
    }

    $guru = User::where('role', 'guru')
        ->orderBy('nama')
        ->get();
    $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
    $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

    return view('dashboard.guru_piket.edit', compact(
        'user',
        'guruPiket',
        'guru',
        'tahunAjaran',
        'tahunAjaranAktif'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN GURU PIKET
|--------------------------------------------------------------------------
*/
Route::post(

    '/dashboard/admin/guru-piket/store',

    function (

        Request $request

    ) {

        $request->validate([

            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',

            'guru_id' => 'required|array',

            'hari' => 'required',

            'jam_mulai' => 'required',

            'jam_selesai' => 'required',

        ]);

        if (

            count(
                $request->guru_id
            )

            <

            5

        ) {

            return back()
                ->with(

                    'error',

                    'Minimal 5 guru piket'

                );

        }

        if ($pesanBentrok = validasiBentrokGuruPiket($request)) {
            return back()
                ->withInput()
                ->with('error', $pesanBentrok);
        }

        foreach (

            $request->guru_id as $guruId

        ) {

            $cek = DB::table(
                'guru_pikets'
            )
                ->where(
                    'guru_id',
                    $guruId
                )
                ->where(
                    'hari',
                    strtolower(
                        $request->hari
                    )
                )
                ->where('jam_mulai', '<', $request->jam_selesai)
                ->where('jam_selesai', '>', $request->jam_mulai)
                ->exists();

            if (
                $cek
            ) {

                continue;

            }

            $newId = DB::table(
                'guru_pikets'
            )
                ->insertGetId([

                    'guru_id' => $guruId,

                    'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),

                    'guru_pengganti_id' => $request->guru_pengganti_id

                    ??

                    null,

                    'guru_pengganti2_id' => $request->guru_pengganti2_id

                    ??

                    null,

                    'hari' => strtolower(
                        $request->hari
                    ),

                    'jam_mulai' => $request->jam_mulai,

                    'jam_selesai' => $request->jam_selesai,

                    'status' => 'Akan Bertugas',

                    'aktif' => 1,

                    'created_at' => now(),

                    'updated_at' => now(),

                ]);
            AuditLogger::record('create', 'guru_pikets', (int) $newId, 'Guru piket ditambahkan', null, DB::table('guru_pikets')->where('id', $newId)->first(), $request);

        }

        return redirect(

            '/dashboard/admin/guru-piket'

        )
            ->with(

                'success',

                'Guru piket berhasil ditambahkan'

            );

    })
    ->middleware(
        'webrole:admin'
    );

Route::post('/dashboard/admin/guru-piket/update/{id}', function (Request $request, $id) {

    $request->validate([
        'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        'guru_id' => 'required',
        'hari' => 'required',
        'jam_mulai' => 'required',
        'jam_selesai' => 'required',
        'guru_pengganti_id' => 'nullable',
        'guru_pengganti2_id' => 'nullable',
        'status' => 'required|in:Akan Bertugas,Sedang Bertugas,Izin,Sakit,Digantikan,Selesai',
    ]);

    $cek = DB::table('guru_pikets')
        ->where('guru_id', $request->guru_id)
        ->where('hari', strtolower($request->hari))
        ->where('id', '!=', $id)
        ->where('jam_mulai', '<', $request->jam_selesai)
        ->where('jam_selesai', '>', $request->jam_mulai)
        ->exists();

    if ($cek) {
        return back()
            ->withInput()
            ->with('error', 'Guru tersebut sudah terdaftar sebagai guru piket pada jam yang sama.');
    }

    if ($pesanBentrok = validasiBentrokGuruPiket($request, (int) $id)) {
        return back()
            ->withInput()
            ->with('error', $pesanBentrok);
    }

    $before = DB::table('guru_pikets')->where('id', $id)->first();

    DB::table('guru_pikets')
        ->where('id', $id)
        ->update([
            'guru_id' => $request->guru_id,
            'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),
            'guru_pengganti_id' => $request->guru_pengganti_id ?: null,
            'guru_pengganti2_id' => $request->guru_pengganti2_id ?: null,
            'hari' => strtolower($request->hari),
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'status' => $request->status,
            'aktif' => $request->has('aktif') ? 1 : 0,
            'updated_at' => now(),
        ]);
    AuditLogger::record('update', 'guru_pikets', (int) $id, 'Guru piket diupdate', $before, DB::table('guru_pikets')->where('id', $id)->first(), $request);

    return redirect('/dashboard/admin/guru-piket')
        ->with('success', 'Guru piket berhasil diupdate');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS GURU PIKET
|--------------------------------------------------------------------------
*/
Route::get(

    '/dashboard/admin/guru-piket/delete/{id}',

    function (

        $id

    ) {

        $data = DB::table(
            'guru_pikets'
        )
            ->where(
                'id',
                $id
            )
            ->first();

        DB::table(
            'guru_pikets'
        )
            ->where(
                'id',
                $id
            )
            ->delete();
        AuditLogger::record('delete', 'guru_pikets', (int) $id, 'Guru piket dihapus', $data, null, request());

        return redirect(

            '/dashboard/admin/guru-piket'

        )
            ->with(

                'success',

                'Guru piket berhasil dihapus'

            );

    })
    ->middleware(
        'webrole:admin'
    );
/*
|--------------------------------------------------------------------------
| LIST JURUSAN
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jurusan', function () {

    $user = session('user');

    $jurusan = DB::table('jurusan')
        ->latest('id')
        ->get();

    return view('dashboard.jurusan.index', compact(
        'user',
        'jurusan'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM TAMBAH JURUSAN
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jurusan/create', function () {

    $user = session('user');

    return view('dashboard.jurusan.create', compact(
        'user'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN JURUSAN
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/jurusan/store', function (Request $request) {

    $request->validate([
        'nama_jurusan' => 'required',
        'kode_jurusan' => 'required',
    ]);

    DB::table('jurusan')->insert([

        'nama_jurusan' => $request->nama_jurusan,
        'kode_jurusan' => $request->kode_jurusan,

        'created_at' => now(),
        'updated_at' => now(),

    ]);

    return redirect('/dashboard/admin/jurusan')
        ->with('success', 'Jurusan berhasil ditambahkan');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS JURUSAN
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jurusan/delete/{id}', function ($id) {

    DB::table('jurusan')
        ->where('id', $id)
        ->delete();

    return redirect('/dashboard/admin/jurusan')
        ->with('success', 'Jurusan berhasil dihapus');

})->middleware('webrole:admin');
