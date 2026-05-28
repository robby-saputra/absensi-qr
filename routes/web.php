<?php

use App\Http\Controllers\Admin\AdminFeatureController;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Web\AuthWebController;
use App\Models\QrCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

/*
|--------------------------------------------------------------------------
| LOGIN WEB
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::get('/logout', [AuthWebController::class, 'logout']);

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

Route::get('/dashboard/admin/notifikasi', function () {

    $user = session('user');

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
                'tipe' => 'Jadwal Pelajaran',
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
                'tipe' => 'Guru Piket',
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
        ->limit(20)
        ->get()
        ->map(function ($item) {
            if ($item->judul === 'Absensi Siswa Diubah Guru Mapel') {
                preg_match(
                    '/^(.*?) mengubah absensi (.*?) kelas (.*?) tanggal (.*?)\. Status masuk: (.*?), status pulang: (.*?)\.$/',
                    $item->pesan ?? '',
                    $matches
                );

                return (object) [
                    'tipe' => 'Absensi Siswa',
                    'judul' => $item->judul,
                    'utama' => $matches[1] ?? '-',
                    'pengganti' => $matches[2] ?? '-',
                    'alasan' => 'Masuk: '.($matches[5] ?? '-').' | Pulang: '.($matches[6] ?? '-'),
                    'detail' => 'Kelas: '.($matches[3] ?? '-'),
                    'waktu' => $matches[4] ?? '-',
                    'label_utama' => 'Guru mapel',
                    'label_pengganti' => 'Siswa',
                    'label_alasan' => 'Status',
                    'created_at' => $item->created_at,
                ];
            }

            return (object) [
                'tipe' => 'Sistem',
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

    DB::table('notifications')
        ->whereNull('user_id')
        ->where('status', 'belum_dibaca')
        ->update([
            'status' => 'dibaca',
            'updated_at' => now(),
        ]);

    return view('dashboard.notifikasi', compact('user', 'notifikasi'));

})->middleware('webrole:admin');

Route::middleware('webrole:admin')->group(function () {
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

        $query = DB::table('absensi_mapels as a')
            ->join('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->select(
                'a.*',
                's.nama as nama_siswa',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as nama_guru',
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

        $data = $query
            ->latest('a.tanggal')
            ->orderBy('k.nama_kelas')
            ->orderBy('s.nama')
            ->get();

        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

        return view('dashboard.rekap.absensi_mapel', compact('user', 'data', 'tanggal', 'kelasId', 'kelas'));
    });

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

    User::create([
        'nama' => $request->nama,
        'nuptk' => $request->nuptk,
        'username' => $request->username,
        'password' => Hash::make($request->password),
        'role' => 'guru',
        'no_ortu' => null,
    ]);

    return redirect('/dashboard/admin/guru');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru/delete/{id}', function ($id) {

    User::where('id', $id)
        ->where('role', 'guru')
        ->delete();

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

    return view('dashboard.jadwal.create', compact(
        'user',
        'kelas',
        'mapels',
        'guru'
    ));

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

    return view('dashboard.jadwal.edit', compact(
        'user',
        'jadwal',
        'kelas',
        'mapels',
        'guru'
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

            'kelas_id' => 'required',

            'hari' => 'required',

            'jam_mulai' => 'required',

            'jam_selesai' => 'required',

            'mapel_id' => 'required',

            'guru_id' => 'required',

            'guru_pengganti_id' => 'nullable',

            'keterangan' => 'nullable',

        ]);

        $hariPiket = strtolower($request->hari);

        $guruSedangPiket = DB::table('guru_pikets')
            ->where('hari', $hariPiket)
            ->where('aktif', 1)
            ->whereIn('guru_id', array_filter([
                $request->guru_id,
                $request->guru_pengganti_id,
            ]))
            ->pluck('guru_id')
            ->toArray();

        if (! empty($guruSedangPiket)) {
            $namaGuruPiket = User::whereIn('id', $guruSedangPiket)
                ->orderBy('nama')
                ->pluck('nama')
                ->implode(', ');

            return back()
                ->withInput()
                ->with('error', 'Guru '.$namaGuruPiket.' sudah bertugas sebagai guru piket pada hari '.$request->hari.', jadi tidak bisa diberi jam mengajar.');
        }

        DB::table(

            'jadwal_pelajarans'

        )
            ->insert([

                'kelas_id' => $request->kelas_id,

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
        'kelas_id' => 'required',
        'hari' => 'required',
        'jam_mulai' => 'required',
        'jam_selesai' => 'required',
        'mapel_id' => 'required',
        'guru_id' => 'required',
        'guru_pengganti_id' => 'nullable',
        'keterangan' => 'nullable',
    ]);

    $hariPiket = strtolower($request->hari);

    $guruSedangPiket = DB::table('guru_pikets')
        ->where('hari', $hariPiket)
        ->where('aktif', 1)
        ->whereIn('guru_id', array_filter([
            $request->guru_id,
            $request->guru_pengganti_id,
        ]))
        ->pluck('guru_id')
        ->toArray();

    if (! empty($guruSedangPiket)) {
        $namaGuruPiket = User::whereIn('id', $guruSedangPiket)
            ->orderBy('nama')
            ->pluck('nama')
            ->implode(', ');

        return back()
            ->withInput()
            ->with('error', 'Guru '.$namaGuruPiket.' sudah bertugas sebagai guru piket pada hari '.$request->hari.', jadi tidak bisa diberi jam mengajar.');
    }

    DB::table('jadwal_pelajarans')
        ->where('id', $id)
        ->update([
            'kelas_id' => $request->kelas_id,
            'hari' => $request->hari,
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'mapel_id' => $request->mapel_id,
            'guru_id' => $request->guru_id,
            'guru_pengganti_id' => $request->guru_pengganti_id ?: null,
            'keterangan' => $request->keterangan ?: null,
            'updated_at' => now(),
        ]);

    return redirect('/dashboard/admin/jadwal')
        ->with('success', 'Jadwal berhasil diupdate');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal/delete/{id}', function ($id) {

    DB::table('jadwal_pelajarans')
        ->where('id', $id)
        ->delete();

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

    User::create([

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

        'created_at' => now(),
        'updated_at' => now(),

    ]);

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

            'updated_at' => now(),

        ]);

    return redirect('/dashboard/admin/siswa')
        ->with('success', 'Data siswa berhasil diupdate');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/delete/{id}', function ($id) {

    User::where('id', $id)
        ->where('role', 'siswa')
        ->delete();

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

    $absensiSiswa = DB::table('users as s')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->leftJoin('absensis as a', function ($join) {
            $join->on('a.id_siswa', '=', 's.id')
                ->whereDate('a.tanggal', now()->toDateString());
        })
        ->where('s.role', 'siswa')
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

    return view('dashboard.piket', compact(
        'user',
        'qr',
        'tipe',
        'totalSiswa',
        'absensiSiswa',
        'jadwalPiketHariIni',
        'jadwalMenggantikanHariIni'
    ));

})->middleware('webrole:piket,guru');

/*
|--------------------------------------------------------------------------
| GENERATE QR
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/piket/generate-qr', function (Request $request) {

    $user = session('user');

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

        DB::table('notifications')->insert([
            'user_id' => null,
            'judul' => 'Guru Piket Tidak Hadir',
            'pesan' => $user->nama.' '.$request->status.' sebagai guru piket. Pengganti: '.($namaPenggantiPiket ?: '-').'. Jadwal pelajaran dialihkan: '.$jadwalDialihkan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($penggantiPiket as $penggantiId) {
            DB::table('notifications')->insert([
                'user_id' => $penggantiId,
                'judul' => 'Tugas Guru Piket Pengganti',
                'pesan' => 'Anda menggantikan '.$user->nama.' sebagai guru piket karena '.$request->status.'.',
                'created_at' => now(),
                'updated_at' => now(),
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

        $kelasAjarIds = $jadwal
            ->pluck('kelas_id')
            ->filter()
            ->unique()
            ->values();

        $absensiKelasAjar = collect();
        $riwayatAbsensiKelasAjar = collect();
        $tanggalFilter = $request->get('tanggal', now()->toDateString());
        $hariFilter = $request->get('hari');
        $bulanFilter = $request->get('bulan');
        $tahunFilter = $request->get('tahun');
        $kelasFilter = $request->get('kelas_id');
        $jurusanFilter = $request->get('jurusan_id');
        $kelasAjar = collect();
        $jurusan = DB::table('jurusan')->orderBy('nama_jurusan')->get();

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

                'riwayatAbsensiKelasAjar'

                ,
                'kelasAjar',

                'jurusan',

                'tanggalFilter',

                'hariFilter',

                'bulanFilter',

                'tahunFilter',

                'kelasFilter',

                'jurusanFilter'

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
        'jam_masuk' => $request->jam_masuk ?: null,
        'jam_pulang' => $request->jam_pulang ?: null,
        'status_masuk' => $statusMasuk,
        'status_pulang' => $statusPulang,
        'updated_at' => now(),
    ];

    if ($existing) {
        DB::table('absensis')->where('id', $existing->id)->update($payload);
    } else {
        DB::table('absensis')->insert($payload + [
            'id_siswa' => $siswa->id,
            'tanggal' => $request->tanggal,
            'created_at' => now(),
        ]);
    }

    $kelasSiswa = DB::table('kelas')
        ->where('id', $siswa->kelas_id)
        ->first();

    if (in_array($statusMasuk, ['izin', 'sakit']) || in_array($statusPulang, ['izin', 'sakit'])) {
        DB::table('notifications')->insert([
            'user_id' => null,
            'judul' => 'Absensi Siswa Diubah Guru Mapel',
            'pesan' => $user->nama.' mengubah absensi '.$siswa->nama.' kelas '.($kelasSiswa->nama_kelas ?? '-').' tanggal '.$request->tanggal.'. Status masuk: '.($statusMasuk ?: '-').', status pulang: '.($statusPulang ?: '-').'.',
            'status' => 'belum_dibaca',
            'created_at' => now(),
            'updated_at' => now(),
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

        DB::table(

            'notifications'

        )
            ->insert([

                'user_id' => null,

                'judul' => 'Guru Tidak Hadir',

                'pesan' => $user->nama

                .' '

                .$status

                .' → digantikan '

                .$data->guru_pengganti

                .' | '

                .$data->nama_kelas

                .' | '

                .$data->jam_mulai

                .'-'

                .$data->jam_selesai,

                'created_at' => now(),

                'updated_at' => now(),

            ]);

        /*
        ====================
        NOTIF GURU PENGGANTI
        ====================
        */

        if (

            $data->guru_pengganti_id

        ) {

            DB::table(

                'notifications'

            )
                ->insert([

                    'user_id' => $data->guru_pengganti_id,

                    'judul' => 'Jadwal Pengganti',

                    'pesan' => 'Anda menggantikan '

                    .$user->nama

                    .' kelas '

                    .$data->nama_kelas

                    .' '

                    .$data->jam_mulai

                    .'-'

                    .$data->jam_selesai,

                    'created_at' => now(),

                    'updated_at' => now(),

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

        return view(

            'dashboard.guru_piket.create',

            compact(

                'user',

                'guru'

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

    return view('dashboard.guru_piket.edit', compact(
        'user',
        'guruPiket',
        'guru'
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
                ->exists();

            if (
                $cek
            ) {

                continue;

            }

            DB::table(
                'guru_pikets'
            )
                ->insert([

                    'guru_id' => $guruId,

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
        ->exists();

    if ($cek) {
        return back()
            ->withInput()
            ->with('error', 'Guru tersebut sudah terdaftar sebagai guru piket pada hari yang sama.');
    }

    DB::table('guru_pikets')
        ->where('id', $id)
        ->update([
            'guru_id' => $request->guru_id,
            'guru_pengganti_id' => $request->guru_pengganti_id ?: null,
            'guru_pengganti2_id' => $request->guru_pengganti2_id ?: null,
            'hari' => strtolower($request->hari),
            'jam_mulai' => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'status' => $request->status,
            'aktif' => $request->has('aktif') ? 1 : 0,
            'updated_at' => now(),
        ]);

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
