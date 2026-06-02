<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

    public function store(Request $request)
    {
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

    public function update(Request $request, $id)
    {
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
    }

    public function delete(Request $request, $id)
    {
        wajibSuperadmin();

        $target = User::findOrFail($id);

        if ($target->role === 'admin' && $target->admin_level === 'superadmin') {
            return back()->with('error', 'Superadmin aktif tidak boleh dihapus agar akses sistem tetap aman.');
        }

        arsipkanData('users', (int) $id, 'User', $request);

        return back()->with('success', 'User berhasil dihapus.');
    }
}
