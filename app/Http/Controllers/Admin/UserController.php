<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        wajibSuperadmin();

        $user = session('user');
        $filters = [
            'q' => trim((string) $request->get('q', '')),
            'role' => strtolower(trim((string) $request->get('role', ''))),
            'status' => strtolower(trim((string) $request->get('status', ''))),
            'kelas_id' => trim((string) $request->get('kelas_id', '')),
            'admin_level' => strtolower(trim((string) $request->get('admin_level', ''))),
        ];

        $query = User::with('kelasRelasi');

        if ($filters['q'] !== '') {
            $query->where(function ($search) use ($filters) {
                $search->where('nama', 'like', '%'.$filters['q'].'%')
                    ->orWhere('username', 'like', '%'.$filters['q'].'%')
                    ->orWhere('nis', 'like', '%'.$filters['q'].'%')
                    ->orWhere('nuptk', 'like', '%'.$filters['q'].'%')
                    ->orWhere('nama_ortu', 'like', '%'.$filters['q'].'%')
                    ->orWhere('no_ortu', 'like', '%'.$filters['q'].'%')
                    ->orWhere('role', 'like', '%'.$filters['q'].'%')
                    ->orWhere('admin_level', 'like', '%'.$filters['q'].'%')
                    ->orWhereHas('kelasRelasi', function ($kelas) use ($filters) {
                        $kelas->where('nama_kelas', 'like', '%'.$filters['q'].'%');
                    });
            });
        }

        if ($filters['role'] !== '') {
            $query->whereRaw('LOWER(TRIM(role)) = ?', [$filters['role']]);
        }

        if ($filters['status'] !== '') {
            $query->where('aktif', $filters['status'] === 'aktif' ? 1 : 0);
        }

        if ($filters['kelas_id'] !== '') {
            $query->where('kelas_id', $filters['kelas_id']);
        }

        if ($filters['admin_level'] !== '') {
            $query->whereRaw('LOWER(TRIM(admin_level)) = ?', [$filters['admin_level']]);
        }

        $users = $query->orderBy('role')->orderBy('nama')->get();
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $ringkasan = [
            'total' => $users->count(),
            'aktif' => $users->where('aktif', 1)->count(),
            'nonaktif' => $users->where('aktif', 0)->count(),
        ];

        return view('dashboard.users_admin.index', compact('user', 'users', 'kelas', 'filters', 'ringkasan'));
    }

    public function create()
    {
        wajibSuperadmin();

        $user = session('user');
        $target = null;
        $mode = 'create';
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

        return view('dashboard.users_admin.form', compact('user', 'target', 'mode', 'kelas'));
    }

    public function edit($id)
    {
        wajibSuperadmin();

        $user = session('user');
        $target = User::findOrFail($id);
        $mode = 'edit';
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

        return view('dashboard.users_admin.form', compact('user', 'target', 'mode', 'kelas'));
    }
}
