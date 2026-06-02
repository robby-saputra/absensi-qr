<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class BantuanController extends Controller
{
    public function dashboard()
    {
        $user = session('user');
        $role = $user->role ?? 'siswa';
        $isWali = $role === 'guru' && DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
        $isPiket = ($role === 'piket') || ($role === 'guru' && DB::table('guru_pikets')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($user) {
                $query->where('guru_id', $user->id)
                    ->orWhere('guru_pengganti_id', $user->id)
                    ->orWhere('guru_pengganti2_id', $user->id);
            })
            ->exists());
        $context = request('context');
        $allowedContexts = collect([$role]);
        if ($role === 'admin') {
            $allowedContexts->push('admin');
        }
        if ($role === 'siswa') {
            $allowedContexts->push('siswa');
        }
        if ($role === 'guru') {
            $allowedContexts->push('guru');
            if ($isWali) {
                $allowedContexts->push('wali');
            }
            if ($isPiket) {
                $allowedContexts->push('piket');
            }
        }
        if ($role === 'piket') {
            $allowedContexts->push('piket');
        }
        $targetRole = $allowedContexts->contains($context) ? $context : ($role === 'piket' ? 'piket' : ($role === 'admin' ? 'admin' : ($role === 'siswa' ? 'siswa' : 'guru')));

        return view('dashboard.bantuan', compact('user', 'role', 'isWali', 'isPiket', 'targetRole'));
    }

    public function public()
    {
        $user = null;
        $role = 'publik';
        $isWali = false;
        $isPiket = false;
        $targetRole = 'publik';

        return view('dashboard.bantuan', compact('user', 'role', 'isWali', 'isPiket', 'targetRole'));
    }
}
