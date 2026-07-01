<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class WebRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Middleware ini menjaga halaman website agar hanya bisa dibuka user yang sudah login.
        $user = session('user');

        // Jika session user belum ada, pengguna diarahkan kembali ke halaman login.
        if (! $user) {
            return redirect('/login')->with('error', 'Silakan login dulu');
        }

        // Data user diambil ulang dari database supaya status akun terbaru tetap terbaca.
        $user = User::find($user->id);

        // Jika akun sudah dihapus atau dinonaktifkan admin, session dibersihkan dan akses ditolak.
        if (! $user || ! (bool) ($user->aktif ?? true)) {
            session()->forget('user');

            return redirect('/login')->with('error', 'Akun Anda sedang dinonaktifkan');
        }

        // Session diperbarui dengan data terbaru dari database.
        session(['user' => $user]);

        // Role dicek agar halaman admin/guru/piket/siswa tidak saling bisa dibuka sembarangan.
        if (! in_array($user->role, $roles)) {
            return abort(403, 'Akses ditolak');
        }

        // Jika login dan role valid, request boleh lanjut ke controller tujuan.
        return $next($request);
    }
}
