<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Admin\AdminFeatureController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Api\AbsensiController;
use App\Models\User;
use App\Models\QrCode;


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

function(){

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








return view(

'dashboard.admin',

compact(

'user',

'totalSiswa',

'totalGuru',

'totalKelas',

'totalJurusan'

)

);

})

->middleware(

'webrole:admin'

);

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

'kelas_id'

=>

'required',



'hari'

=>

'required',



'jam_mulai'

=>

'required',



'jam_selesai'

=>

'required',



'mapel_id'

=>

'required',



'guru_id'

=>

'required',



'guru_pengganti_id'

=>

'nullable',



'keterangan'

=>

'nullable'

]);






DB::table(

'jadwal_pelajarans'

)

->insert([



'kelas_id'

=>

$request->kelas_id,





'hari'

=>

$request->hari,





'jam_mulai'

=>

$request->jam_mulai,





'jam_selesai'

=>

$request->jam_selesai,





'mapel_id'

=>

$request->mapel_id,





'guru_id'

=>

$request->guru_id,





/*
==================================
Guru pengganti
==================================
*/

'guru_pengganti_id'

=>

$request->guru_pengganti_id

??

null,






/*
==================================
PENTING:
Awal = BELUM PILIH STATUS
==================================
*/

'status_guru'

=>

null,






'alasan_tidak_hadir'

=>

null,







'keterangan'

=>

$request->keterangan

??

null,








'created_at'

=>

now(),





'updated_at'

=>

now(),

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
| MONITORING NILAI
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/nilai', function () {

    $user = session('user');

    $nilai = DB::table('nilais as n')
        ->join('users as s', 's.id', '=', 'n.siswa_id')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->join('users as g', 'g.id', '=', 'n.guru_id')
        ->join('mapels as m', 'm.id', '=', 'n.mapel_id')
        ->select(
            'n.*',
            's.nama as nama_siswa',
            'k.nama_kelas',
            'g.nama as nama_guru',
            'm.nama_mapel'
        )
        ->latest('n.id')
        ->get();

    return view('dashboard.nilai.index', compact(
        'user',
        'nilai'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM EDIT NILAI
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/nilai/edit/{id}', function ($id) {

    $user = session('user');

    $nilai = DB::table('nilais')->where('id', $id)->first();

    if (!$nilai) {
        abort(404);
    }

    return view('dashboard.nilai.edit', compact(
        'user',
        'nilai'
    ));

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| UPDATE NILAI
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/nilai/update/{id}', function (Request $request, $id) {

    $request->validate([
        'nilai' => 'required|numeric|min:0|max:100'
    ]);

    DB::table('nilais')
        ->where('id', $id)
        ->update([
            'nilai' => $request->nilai,
            'updated_at' => now(),
        ]);

    return redirect('/dashboard/admin/nilai');

})->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS NILAI
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/nilai/delete/{id}', function ($id) {

    DB::table('nilais')
        ->where('id', $id)
        ->delete();

    return redirect('/dashboard/admin/nilai');

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

    $query->where(function($q) use ($request){

        $q->where(
            's.nama',
            'like',
            '%' . $request->search . '%'
        )

        ->orWhere(
            's.nis',
            'like',
            '%' . $request->search . '%'
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
        $request->tingkat . '%'
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

            'wali_kelas_id'

            =>

            $request->guru_id,

            'updated_at'

            =>

            now(),

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

'wali_kelas_id'

=>

'required'

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

'wali_kelas_id'

=>

$request->wali_kelas_id,

'updated_at'

=>

now()

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

            'wali_kelas_id'

            =>

            null,

            'updated_at'

            =>

            now(),

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

    $tipe = $request->get('tipe', 'masuk');

    $qr = QrCode::whereDate('tanggal', now()->toDateString())
        ->where('tipe', $tipe)
        ->latest('id')
        ->first();

    return view('dashboard.piket', compact('user', 'qr', 'tipe'));

})->middleware('webrole:piket');

/*
|--------------------------------------------------------------------------
| GENERATE QR
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/piket/generate-qr', function (Request $request) {

    $request->validate([
        'tipe' => 'required|in:masuk,pulang'
    ]);

    QrCode::create([
        'tanggal' => now()->toDateString(),
        'tipe' => $request->tipe,
        'token' => Str::random(12),
    ]);

    return redirect('/dashboard/piket?tipe=' . $request->tipe);

})->middleware('webrole:piket');

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
    if (!$wali) {
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

    return view('dashboard.wali', compact(
        'user',
        'siswa',
        'wali'
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

    if (!$wali) {
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
| MONITORING NILAI WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali/nilai', function () {

    $user = session('user');

    $wali = DB::table('kelas')
        ->select('id', 'nama_kelas')
        ->where('wali_kelas_id', $user->id)
        ->first();

    if (!$wali) {
        abort(403);
    }

    $nilai = DB::table('nilais as n')
        ->join('users as s', 's.id', '=', 'n.siswa_id')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->join('mapels as m', 'm.id', '=', 'n.mapel_id')
        ->select(
            'n.*',
            's.nama as nama_siswa',
            'k.nama_kelas',
            'm.nama_mapel'
        )
        ->where('s.kelas_id', $wali->id)
        ->latest('n.id')
        ->get();

    return view('dashboard.wali_nilai', compact(
        'user',
        'wali',
        'nilai'
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

    if (!$wali) {
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

function () {


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

->where(function(

$q

)

use(

$user

){


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

$q->orWhere(function(

$x

)

use(

$user

){

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








$isWaliKelas = DB::table(

'kelas'

)

->where(

'wali_kelas_id',

$user->id

)

->exists();







return view(

'dashboard.guru',

compact(

'user',

'jadwal',

'hari',

'isWaliKelas'

)

);



})

->middleware(

'webrole:guru'

);
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

function(

Request $request,

$id

){

$user = session('user');

$request->validate([

'status'=>

'required|in:normal,izin,sakit,inval'

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



if(!$jadwal){

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

if(

$jadwal->status_guru

!==

null

){

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

if(

$status

==

'normal'

){

DB::table(

'jadwal_pelajarans'

)

->where(

'id',

$id

)

->update([

'status_guru'=>

'normal',

'alasan_tidak_hadir'=>

null,

'updated_at'=>

now()

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

'status_guru'=>

'digantikan',

'alasan_tidak_hadir'=>

$status,

'updated_at'=>

now()

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

'user_id'=>

null,



'judul'=>

'Guru Tidak Hadir',



'pesan'=>

$user->nama

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



'created_at'=>

now(),

'updated_at'=>

now()

]);








/*
====================
NOTIF GURU PENGGANTI
====================
*/

if(

$data->guru_pengganti_id

){

DB::table(

'notifications'

)

->insert([

'user_id'=>

$data->guru_pengganti_id,



'judul'=>

'Jadwal Pengganti',



'pesan'=>

'Anda menggantikan '

.$user->nama

.' kelas '

.$data->nama_kelas

.' '

.$data->jam_mulai

.'-'

.$data->jam_selesai,



'created_at'=>

now(),

'updated_at'=>

now()

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
        ->where('guru_id', $user->id)
        ->first();

    if (!$jadwal) {
        abort(403);
    }

    $qr = DB::table('qr_sesis')
        ->where('jadwal_id', $jadwalId)
        ->whereDate('tanggal', now()->toDateString())
        ->where('aktif', 1)
        ->first();

    if (!$qr) {
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
| FORM INPUT NILAI GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/guru/nilai/{jadwalId}', function ($jadwalId) {

    $user = session('user');

    $jadwal = DB::table('jadwal_pelajarans as j')
        ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
        ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
        ->select(
            'j.*',
            'k.nama_kelas',
            'm.nama_mapel'
        )
        ->where('j.id', $jadwalId)
        ->where('j.guru_id', $user->id)
        ->first();

    if (!$jadwal) {
        abort(403);
    }

    $siswa = User::where('role', 'siswa')
        ->where('kelas_id', $jadwal->kelas_id)
        ->orderBy('nama')
        ->get();

    return view('dashboard.guru_nilai', compact(
        'user',
        'jadwal',
        'siswa'
    ));

})->middleware('webrole:guru');

/*
|--------------------------------------------------------------------------
| SIMPAN NILAI GURU
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/guru/nilai/store', function (Request $request) {

    $request->validate([
        'siswa_id' => 'required',
        'mapel_id' => 'required',
        'guru_id' => 'required',
        'jenis_nilai' => 'required',
       'nilai' => 'nullable|numeric|min:0|max:100',
'keterangan' => 'nullable',
    ]);

    DB::table('nilais')->insert([
        'siswa_id' => $request->siswa_id,
        'mapel_id' => $request->mapel_id,
        'guru_id' => $request->guru_id,
        'jenis_nilai' => $request->jenis_nilai,
       'nilai' => $request->nilai,
'keterangan' => $request->keterangan,
        'semester' => 'Genap',
        'tahun_ajaran' => '2025/2026',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('success', 'Nilai berhasil disimpan');

})->middleware('webrole:guru');

/*
|--------------------------------------------------------------------------
| NILAI HARI INI
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/guru/nilai-hari-ini/{jadwalId}', function ($jadwalId) {

    $user = session('user');

    $nilai = DB::table('nilais as n')
        ->join('users as s', 's.id', '=', 'n.siswa_id')
        ->join('mapels as m', 'm.id', '=', 'n.mapel_id')
        ->join('jadwal_pelajarans as j', 'j.mapel_id', '=', 'm.id')
        ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
        ->select(
            'n.*',
            's.nama as nama_siswa',
            'k.nama_kelas',
            'm.nama_mapel'
        )
        ->where('n.guru_id', $user->id)
        ->where('j.id', $jadwalId)
        ->whereColumn('s.kelas_id', 'j.kelas_id')
        ->whereDate('n.created_at', now()->toDateString())
        ->latest('n.id')
        ->get();

    return view('dashboard.guru_nilai_hari_ini', compact(
        'user',
        'nilai'
    ));

})->middleware('webrole:guru');

/*
|--------------------------------------------------------------------------
| SEMUA NILAI
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/guru/semua-nilai/{jadwalId}', function ($jadwalId) {

    $user = session('user');

    $nilai = DB::table('nilais as n')
        ->join('users as s', 's.id', '=', 'n.siswa_id')
        ->join('mapels as m', 'm.id', '=', 'n.mapel_id')
        ->join('jadwal_pelajarans as j', 'j.mapel_id', '=', 'm.id')
        ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
        ->select(
            'n.*',
            's.nama as nama_siswa',
            'k.nama_kelas',
            'm.nama_mapel'
        )
        ->where('n.guru_id', $user->id)
        ->where('j.id', $jadwalId)
        ->whereColumn('s.kelas_id', 'j.kelas_id')
        ->latest('n.id')
        ->get();

    return view('dashboard.guru_semua_nilai', compact(
        'user',
        'nilai'
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

    if (!$user) {
        return back()->with('error', 'User tidak login');
    }

    $qr = QrCode::where('token', $request->token)->first();

    if (!$qr) {
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
    if (!empty($hari)) {

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

            !in_array(

                $g->status,

                [

                    'Izin',

                    'Sakit',

                    'Digantikan'

                ]

            )

        ) {


            if (

                $jamSekarang < $g->jam_mulai

            ) {

                $status = 'Akan Bertugas';

            }


            elseif (

                $jamSekarang >= $g->jam_mulai

                &&

                $jamSekarang <= $g->jam_selesai

            ) {

                $status = 'Sedang Bertugas';

            }


            else {

                $status = 'Selesai';

            }



            DB::table('guru_pikets')

                ->where(
                    'id',
                    $g->id
                )

                ->update([

                    'status' => $status,

                    'updated_at' => now()

                ]);


            $g->status = $status;

        }


        elseif (

            $g->hari != $hariSekarang

            &&

            !in_array(

                $g->status,

                [

                    'Izin',

                    'Sakit',

                    'Digantikan'

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

                    'updated_at' => now()

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

'guru_id'

=>

'required|array',


'hari'

=>

'required',


'jam_mulai'

=>

'required',


'jam_selesai'

=>

'required'

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

$request->guru_id

as

$guruId

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


'guru_id'

=>

$guruId,



'guru_pengganti_id'

=>

$request->guru_pengganti_id

??

null,



'guru_pengganti2_id'

=>

$request->guru_pengganti2_id

??

null,



'hari'

=>

strtolower(
$request->hari
),



'jam_mulai'

=>

$request->jam_mulai,



'jam_selesai'

=>

$request->jam_selesai,



'status'

=>

'Akan Bertugas',



'aktif'

=>

1,



'created_at'

=>

now(),



'updated_at'

=>

now()

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
        'kode_jurusan' => 'required'
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
