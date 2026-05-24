<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || ! $this->passwordMatches($request->password, $user->password)) {
            return back()->with('error', 'Username atau password salah');
        }

        if (! (bool) ($user->aktif ?? true)) {
            return back()->with('error', 'Akun Anda sedang dinonaktifkan');
        }

        session([
            'user' => $user
        ]);

        // ADMIN
        if ($user->role === 'admin') {
            return redirect('/dashboard/admin');
        }

        // PIKET
        if ($user->role === 'piket') {
            return redirect('/dashboard/piket');
        }

        // GURU MAPEL / WALI KELAS
        if ($user->role === 'guru') {
            return redirect('/dashboard/guru');
        }

        // SISWA
        if ($user->role === 'siswa') {
            return redirect('/dashboard/users');
        }

        return redirect('/login');
    }

    public function logout()
    {
        session()->forget('user');

        return redirect('/login');
    }

    private function passwordMatches(string $plain, ?string $stored): bool
    {
        if (! $stored) {
            return false;
        }

        $isLaravelHash = str_starts_with($stored, '$2y$')
            || str_starts_with($stored, '$2a$')
            || str_starts_with($stored, '$argon2');

        return ($isLaravelHash && Hash::check($plain, $stored))
            || hash_equals($stored, $plain)
            || (strlen($stored) === 32 && hash_equals($stored, md5($plain)));
    }
}
