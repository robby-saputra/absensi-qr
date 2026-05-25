<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class WebRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = session('user');

        // cek login
        if (! $user) {
            return redirect('/login')->with('error', 'Silakan login dulu');
        }

        $user = User::find($user->id);

        if (! $user || ! (bool) ($user->aktif ?? true)) {
            session()->forget('user');

            return redirect('/login')->with('error', 'Akun Anda sedang dinonaktifkan');
        }

        session(['user' => $user]);

        // cek role
        if (! in_array($user->role, $roles)) {
            return abort(403, 'Akses ditolak');
        }

        return $next($request);
    }
}
