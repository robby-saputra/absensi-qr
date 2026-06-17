<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class RiwayatPerubahanController extends Controller
{
    public function index()
    {
        $user = session('user');
        $kelasIds = $user->role === 'guru' ? kelasAksesGuruIds((int) $user->id) : collect();
        $siswaIds = collect();

        if ($user->role === 'guru') {
            $waliKelasId = DB::table('kelas')->where('wali_kelas_id', $user->id)->value('id');
            $allKelasIds = $kelasIds->merge($waliKelasId ? [$waliKelasId] : [])->filter()->unique();
            $siswaIds = DB::table('users')->where('role', 'siswa')->whereIn('kelas_id', $allKelasIds)->pluck('id');
        }

        $logs = DB::table('audit_logs')
            ->where(function ($query) use ($siswaIds) {
                $query->where(function ($a) use ($siswaIds) {
                    $a->where('tabel', 'absensis')->whereIn('record_id', DB::table('absensis')->whereIn('id_siswa', $siswaIds)->pluck('id'));
                })->orWhere(function ($a) use ($siswaIds) {
                    $a->where('tabel', 'absensi_mapels')->whereIn('record_id', DB::table('absensi_mapels')->whereIn('siswa_id', $siswaIds)->pluck('id'));
                })->orWhere(function ($a) use ($siswaIds) {
                    $a->where('tabel', 'wali_followups')->whereIn('record_id', DB::table('wali_followups')->whereIn('siswa_id', $siswaIds)->pluck('id'));
                });
            })
            ->latest('id')
            ->limit(100)
            ->get();

        return view('dashboard.role_audit', compact('user', 'logs'));
    }
}
