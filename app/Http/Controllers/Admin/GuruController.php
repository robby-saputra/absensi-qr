<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class GuruController extends Controller
{
    public function index()
    {
        $user = session('user');

        $guru = User::where('role', 'guru')
            ->latest('id')
            ->get();

        return view('dashboard.guru.index', compact('user', 'guru'));
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

        arsipkanData('users', (int) $id, 'Data guru', request());

        return redirect('/dashboard/admin/guru');
    }
}
