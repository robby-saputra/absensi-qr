<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PengumumanController extends Controller
{
    public function public()
    {
        $user = session('user');
        abort_if(! $user, 403);
        $role = $user->role === 'piket' ? 'piket' : 'guru';
        $pengumuman = DB::table('announcements')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($role) {
                $q->where('target_role', 'semua')->orWhere('target_role', $role);
                if ($role === 'guru') {
                    $q->orWhere('target_role', 'wali');
                }
            })
            ->where(function ($q) {
                $q->whereNull('tanggal_mulai')->orWhereDate('tanggal_mulai', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', now()->toDateString());
            })
            ->latest('id')
            ->get();

        return view('dashboard.pengumuman.public', compact('user', 'pengumuman'));
    }
}
