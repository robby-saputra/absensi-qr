<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Controller ini mengelola data siswa dari halaman admin.
class SiswaController extends Controller
{
    // Menampilkan daftar siswa dengan filter pencarian, jurusan, tingkat, dan status aktif.
    public function index(Request $request)
    {
        $user = session('user');

        // Query siswa digabung dengan kelas, jurusan, dan wali kelas untuk kebutuhan tabel admin.
        $query = DB::table('users as s')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')
            ->select(
                's.*',
                'k.nama_kelas',
                'w.nama as nama_wali',
                'j.nama_jurusan',
                'j.kode_jurusan'
            )
            ->where('s.role', 'siswa')
            ->whereNull('s.deleted_at');

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
                    )
                    ->orWhere(
                        's.nama_ortu',
                        'like',
                        '%'.$request->search.'%'
                    )
                    ->orWhere(
                        's.no_ortu',
                        'like',
                        '%'.$request->search.'%'
                    );
            });
        }

        if ($request->jurusan) {
            $query->where(
                'j.kode_jurusan',
                $request->jurusan
            );
        }

        if ($request->tingkat) {
            $query->where(
                'k.nama_kelas',
                'like',
                $request->tingkat.'%'
            );
        }

        $status = $request->get('status', 'aktif');
        // Filter status memisahkan siswa aktif, nonaktif, atau semua siswa yang belum dihapus.
        if ($status === 'nonaktif') {
            $query->where('s.aktif', 0);
        } elseif ($status === 'semua') {
            // Tampilkan semua siswa aktif yang belum dihapus.
        } else {
            $query->where('s.aktif', 1);
            $status = 'aktif';
        }

        $siswa = $query
            ->latest('s.id')
            ->get();

        $ringkasanStatus = [
            'aktif' => DB::table('users')->where('role', 'siswa')->whereNull('deleted_at')->where('aktif', 1)->count(),
            'nonaktif' => DB::table('users')->where('role', 'siswa')->whereNull('deleted_at')->where('aktif', 0)->count(),
        ];

        return view('dashboard.siswa.index', compact(
            'user',
            'siswa',
            'status',
            'ringkasanStatus'
        ));
    }

    // Menampilkan form tambah siswa.
    public function create()
    {
        $user = session('user');

        $jurusan = tanpaArsip(DB::table('jurusan'), 'jurusan')
            ->orderBy('kode_jurusan')
            ->get();

        $kelas = tanpaArsip(DB::table('kelas as k'), 'kelas', 'k')
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
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'nis' => 'required|unique:users,nis',
            'username' => 'required|unique:users,username',
            'password' => 'required',
            'kelas_id' => 'required',
        ]);

        $siswa = User::create([
            'nama' => $request->nama,
            'nis' => $request->nis,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => 'siswa',
            'kelas_id' => $request->kelas_id,
            'no_ortu' => $request->no_ortu,
            'nama_ortu' => $request->nama_ortu,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/dashboard/admin/siswa')
            ->with('success', 'Siswa berhasil ditambahkan');
    }

    public function edit($id)
    {
        $user = session('user');

        $siswa = User::where('role', 'siswa')
            ->whereNull('deleted_at')
            ->findOrFail($id);

        $jurusan = tanpaArsip(DB::table('jurusan'), 'jurusan')
            ->orderBy('kode_jurusan')
            ->get();

        $kelas = tanpaArsip(DB::table('kelas as k'), 'kelas', 'k')
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
    }

    public function detail($id)
    {
        $user = session('user');
        $data = detailProfilSiswaData((int) $id);
        $layout = 'admin';

        return view('dashboard.siswa.detail', $data + compact('user', 'layout'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'nis' => 'required',
            'username' => 'required',
            'kelas_id' => 'required',
        ]);

        $before = User::where('id', $id)
            ->where('role', 'siswa')
            ->whereNull('deleted_at')
            ->firstOrFail();

        User::where('id', $id)
            ->where('role', 'siswa')
            ->whereNull('deleted_at')
            ->update([
                'nama' => $request->nama,
                'nis' => $request->nis,
                'username' => $request->username,
                'kelas_id' => $request->kelas_id,
                'no_ortu' => $request->no_ortu,
                'nama_ortu' => $request->nama_ortu,
                'updated_at' => now(),
            ]);

        return redirect('/dashboard/admin/siswa')
            ->with('success', 'Data siswa berhasil diupdate');
    }

    public function delete($id)
    {
        if (! hapusDataAdmin('users', (int) $id, 'Data siswa', request())) {
            return redirect('/dashboard/admin/siswa')
                ->with('error', 'Data siswa gagal dihapus atau data tidak ditemukan.');
        }

        return redirect('/dashboard/admin/siswa')
            ->with('success', 'Data siswa berhasil dipindahkan ke arsip');
    }
}
