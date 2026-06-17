<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $filters = [
            'q' => trim((string) $request->get('q', '')),
            'status' => trim((string) $request->get('status', '')),
            'tugas' => trim((string) $request->get('tugas', '')),
        ];

        $query = User::where('role', 'guru')
            ->whereNull('deleted_at');

        if ($filters['q'] !== '') {
            $query->where(function ($search) use ($filters) {
                $search->where('nama', 'like', '%'.$filters['q'].'%')
                    ->orWhere('nuptk', 'like', '%'.$filters['q'].'%')
                    ->orWhere('username', 'like', '%'.$filters['q'].'%');
            });
        }

        if ($filters['status'] !== '') {
            $query->where('aktif', $filters['status'] === 'aktif' ? 1 : 0);
        }

        if ($filters['tugas'] !== '') {
            $waliIds = DB::table('kelas')
                ->whereNull('deleted_at')
                ->whereNotNull('wali_kelas_id')
                ->pluck('wali_kelas_id');
            $piketIds = DB::table('guru_pikets')
                ->whereNull('deleted_at')
                ->pluck('guru_id');
            $mapelIds = DB::table('jadwal_pelajarans')
                ->whereNull('deleted_at')
                ->pluck('guru_id');

            if ($filters['tugas'] === 'wali') {
                $query->whereIn('id', $waliIds);
            } elseif ($filters['tugas'] === 'piket') {
                $query->whereIn('id', $piketIds);
            } elseif ($filters['tugas'] === 'mapel') {
                $query->whereIn('id', $mapelIds);
            } elseif ($filters['tugas'] === 'tanpa_tugas') {
                $bertugasIds = $waliIds->merge($piketIds)->merge($mapelIds)->unique()->values();
                $query->whereNotIn('id', $bertugasIds);
            }
        }

        $guru = $query
            ->orderBy('nama')
            ->get();

        return view('dashboard.guru.index', compact('user', 'guru', 'filters'));
    }

    public function create()
    {
        $user = session('user');

        return view('dashboard.guru.create', compact('user'));
    }

    public function store(Request $request)
    {
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
    }

    public function delete($id)
    {
        $before = User::where('id', $id)->where('role', 'guru')->first();
        if (! $before) {
            return redirect('/dashboard/admin/guru')
                ->with('error', 'Data guru tidak ditemukan.');
        }

        if (! arsipkanData('users', (int) $id, 'Data guru', request())) {
            return redirect('/dashboard/admin/guru')
                ->with('error', 'Data guru gagal dihapus.');
        }

        return redirect('/dashboard/admin/guru')
            ->with('success', 'Data guru berhasil dihapus.');
    }
}
