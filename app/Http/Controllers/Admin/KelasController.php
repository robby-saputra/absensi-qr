<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    public function index()
    {
        $user = session('user');

        $kelas = tanpaArsip(DB::table('kelas as k'), 'kelas', 'k')

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
        if (! arsipkanData('kelas', (int) $id, 'Data kelas', request())) {
            return redirect('/dashboard/admin/kelas')
                ->with('error', 'Kelas gagal dihapus atau data tidak ditemukan.');
        }

        return redirect('/dashboard/admin/kelas')
            ->with('success', 'Kelas berhasil dihapus');
    }
}

