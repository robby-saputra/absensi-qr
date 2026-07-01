<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controller ini mengelola penugasan wali kelas untuk guru.
class WaliKelasController extends Controller
{
    // Menampilkan daftar kelas yang sudah memiliki wali kelas beserta ringkasan siswanya.
    public function index(Request $request)
    {
        $user = session('user');
        $filters = $request->only(['search', 'jurusan_id', 'siswa_status', 'sort']);

        // Query ini menggabungkan kelas, wali kelas, jurusan, dan jumlah siswa aktif.
        $query = tanpaArsip(DB::table('kelas as k'), 'kelas', 'k')
            ->leftJoin('users as u', 'u.id', '=', 'k.wali_kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->whereNotNull('k.wali_kelas_id')
            ->select(
                'k.id',
                'k.nama_kelas',
                'k.wali_kelas_id',
                'u.nama',
                'u.username',
                'j.id as jurusan_id',
                'j.nama_jurusan',
                'j.kode_jurusan'
            )
            ->selectSub(function ($subquery) {
                $subquery->from('users as siswa')->selectRaw('COUNT(*)')
                    ->whereColumn('siswa.kelas_id', 'k.id')->where('siswa.role', 'siswa')
                    ->where('siswa.aktif', 1)->whereNull('siswa.deleted_at');
            }, 'jumlah_siswa')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($where) use ($search) {
                    $where->where('k.nama_kelas', 'like', '%'.$search.'%')
                        ->orWhere('u.nama', 'like', '%'.$search.'%')
                        ->orWhere('u.username', 'like', '%'.$search.'%')
                        ->orWhere('j.nama_jurusan', 'like', '%'.$search.'%')
                        ->orWhere('j.kode_jurusan', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['jurusan_id'] ?? null, fn ($query, $id) => $query->where('k.jurusan_id', $id))
            ->when(($filters['siswa_status'] ?? null) === 'terisi', fn ($query) => $query->whereExists(function ($subquery) {
                $subquery->selectRaw('1')->from('users as siswa')->whereColumn('siswa.kelas_id', 'k.id')->where('siswa.role', 'siswa')->where('siswa.aktif', 1)->whereNull('siswa.deleted_at');
            }))
            ->when(($filters['siswa_status'] ?? null) === 'kosong', fn ($query) => $query->whereNotExists(function ($subquery) {
                $subquery->selectRaw('1')->from('users as siswa')->whereColumn('siswa.kelas_id', 'k.id')->where('siswa.role', 'siswa')->where('siswa.aktif', 1)->whereNull('siswa.deleted_at');
            }));

        match ($filters['sort'] ?? 'kelas') {
            'wali' => $query->orderBy('u.nama')->orderBy('k.nama_kelas'),
            'siswa_terbanyak' => $query->orderByDesc('jumlah_siswa')->orderBy('k.nama_kelas'),
            'siswa_tersedikit' => $query->orderBy('jumlah_siswa')->orderBy('k.nama_kelas'),
            default => $query->orderBy('k.nama_kelas'),
        };

        $wali = $query->get();
        $jurusan = tanpaArsip(DB::table('jurusan'), 'jurusan')->orderBy('kode_jurusan')->get();
        $kelasAktif = tanpaArsip(DB::table('kelas as ringkas'), 'kelas', 'ringkas');
        // Ringkasan dipakai untuk melihat jumlah kelas dengan wali, tanpa wali, siswa binaan, dan guru.
        $ringkasan = [
            'wali' => (clone $kelasAktif)->whereNotNull('wali_kelas_id')->count(),
            'belum' => (clone $kelasAktif)->whereNull('wali_kelas_id')->count(),
            'siswa' => DB::table('users as siswa')->join('kelas as k', 'k.id', '=', 'siswa.kelas_id')->where('siswa.role', 'siswa')->where('siswa.aktif', 1)->whereNull('siswa.deleted_at')->whereNull('k.deleted_at')->whereNotNull('k.wali_kelas_id')->count(),
            'guru' => User::where('role', 'guru')->where('aktif', 1)->whereNull('deleted_at')->count(),
        ];

        return view(
            'dashboard.wali_kelas.index',
            compact(
                'user',
                'wali',
                'jurusan',
                'filters',
                'ringkasan'
            )
        );
    }

    // Menampilkan form penugasan wali kelas baru.
    public function create()
    {
        $user = session('user');

        $guru = User::where('role', 'guru')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get();

        $kelas = DB::table('kelas')
            ->orderBy('nama_kelas')
            ->get();

        return view('dashboard.wali_kelas.create', compact(
            'user',
            'guru',
            'kelas'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'guru_id' => 'required',
            'kelas_id' => 'required',
        ]);

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

        if ($pesanGuruNonaktif = validasiGuruAktifIds([$request->guru_id])) {
            return back()
                ->withInput()
                ->with('error', $pesanGuruNonaktif);
        }

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
    }

    public function edit($id)
    {
        $user = session('user');

        $kelas = DB::table('kelas as k')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')
            ->select('k.*', 'j.nama_jurusan', 'j.kode_jurusan', 'w.nama as nama_wali', 'w.username as username_wali')
            ->selectSub(function ($subquery) {
                $subquery->from('users as siswa')->selectRaw('COUNT(*)')->whereColumn('siswa.kelas_id', 'k.id')->where('siswa.role', 'siswa')->where('siswa.aktif', 1)->whereNull('siswa.deleted_at');
            }, 'jumlah_siswa')
            ->where('k.id', $id)
            ->first();

        abort_unless($kelas, 404);

        $guru = User::where('role', 'guru')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
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
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'wali_kelas_id' => 'required',
        ]);

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

        if ($pesanGuruNonaktif = validasiGuruAktifIds([$request->wali_kelas_id])) {
            return back()
                ->withInput()
                ->with('error', $pesanGuruNonaktif);
        }

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
    }

    public function delete($id)
    {
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
    }
}
