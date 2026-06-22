<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $filters = $request->only(['search', 'jurusan_id', 'wali_status', 'siswa_status']);

        $query = tanpaArsip(DB::table('kelas as k'), 'kelas', 'k')

            ->leftJoin('users as u', 'u.id', '=', 'k.wali_kelas_id')

            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')

            ->select(
                'k.*',
                'u.nama as nama_wali',
                'j.nama_jurusan',
                'j.kode_jurusan'
            )

            ->selectSub(function ($subquery) {
                $subquery->from('users as siswa')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('siswa.kelas_id', 'k.id')
                    ->where('siswa.role', 'siswa')
                    ->where('siswa.aktif', 1)
                    ->whereNull('siswa.deleted_at');
            }, 'jumlah_siswa');

        $query
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($where) use ($search) {
                    $where->where('k.nama_kelas', 'like', '%'.$search.'%')
                        ->orWhere('j.nama_jurusan', 'like', '%'.$search.'%')
                        ->orWhere('j.kode_jurusan', 'like', '%'.$search.'%')
                        ->orWhere('u.nama', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['jurusan_id'] ?? null, fn ($query, $id) => $query->where('k.jurusan_id', $id))
            ->when(($filters['wali_status'] ?? null) === 'ada', fn ($query) => $query->whereNotNull('k.wali_kelas_id'))
            ->when(($filters['wali_status'] ?? null) === 'belum', fn ($query) => $query->whereNull('k.wali_kelas_id'))
            ->when(($filters['siswa_status'] ?? null) === 'terisi', fn ($query) => $query->whereExists(function ($subquery) {
                $subquery->selectRaw('1')->from('users as siswa')->whereColumn('siswa.kelas_id', 'k.id')->where('siswa.role', 'siswa')->where('siswa.aktif', 1)->whereNull('siswa.deleted_at');
            }))
            ->when(($filters['siswa_status'] ?? null) === 'kosong', fn ($query) => $query->whereNotExists(function ($subquery) {
                $subquery->selectRaw('1')->from('users as siswa')->whereColumn('siswa.kelas_id', 'k.id')->where('siswa.role', 'siswa')->where('siswa.aktif', 1)->whereNull('siswa.deleted_at');
            }));

        $kelas = $query

            ->orderBy('k.nama_kelas')

            ->get();

        $jurusan = tanpaArsip(DB::table('jurusan'), 'jurusan')->orderBy('kode_jurusan')->get();
        $kelasAktifQuery = tanpaArsip(DB::table('kelas as ringkas'), 'kelas', 'ringkas');
        $ringkasan = [
            'kelas' => (clone $kelasAktifQuery)->count(),
            'siswa' => DB::table('users')->where('role', 'siswa')->where('aktif', 1)->whereNull('deleted_at')->whereNotNull('kelas_id')->count(),
            'tanpa_wali' => (clone $kelasAktifQuery)->whereNull('wali_kelas_id')->count(),
            'kosong' => (clone $kelasAktifQuery)->whereNotExists(function ($subquery) {
                $subquery->selectRaw('1')->from('users as siswa')->whereColumn('siswa.kelas_id', 'ringkas.id')->where('siswa.role', 'siswa')->where('siswa.aktif', 1)->whereNull('siswa.deleted_at');
            })->count(),
        ];

        return view('dashboard.kelas.index', compact(
            'user',
            'kelas',
            'jurusan',
            'filters',
            'ringkasan'
        ));
    }

    public function create()
    {
        $user = session('user');

        $guru = User::where('role', 'guru')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get();

        $jurusan = tanpaArsip(DB::table('jurusan'), 'jurusan')
            ->orderBy('kode_jurusan')
            ->get();

        return view('dashboard.kelas.create', compact(
            'user',
            'guru',
            'jurusan'
        ));
    }

    public function store(Request $request)
    {
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

            if ($pesanGuruNonaktif = validasiGuruAktifIds([$request->wali_kelas_id])) {
                return back()
                    ->withInput()
                    ->with('error', $pesanGuruNonaktif);
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
    }

    public function delete($id)
    {
        if (! hapusDataAdmin('kelas', (int) $id, 'Data kelas', request())) {
            return redirect('/dashboard/admin/kelas')
                ->with('error', 'Kelas gagal dihapus atau data tidak ditemukan.');
        }

        return redirect('/dashboard/admin/kelas')
            ->with('success', 'Kelas berhasil dipindahkan ke arsip');
    }
}
