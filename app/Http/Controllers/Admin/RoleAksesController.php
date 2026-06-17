<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoleAksesController extends Controller
{
    public function index()
    {
        wajibSuperadmin();
        $user = session('user');
        $guruWali = User::where('role', 'guru')->whereIn('id', DB::table('kelas')->whereNotNull('wali_kelas_id')->pluck('wali_kelas_id'))->get();
        $guruPiket = User::where('role', 'guru')->whereIn('id', DB::table('guru_pikets')->whereNull('deleted_at')->pluck('guru_id'))->get();
        $siswaAktif = User::where('role', 'siswa')->where('aktif', 1)->count();
        $siswaNonaktif = User::where('role', 'siswa')->where('aktif', 0)->count();
        $akunTanpaLogin = User::whereNotIn('id', DB::table('user_login_statuses')->pluck('user_id'))->orderBy('role')->orderBy('nama')->get();
        $akunAkses = User::with('kelasRelasi')->orderBy('role')->orderBy('nama')->get();

        return view('dashboard.role_akses', compact('user', 'guruWali', 'guruPiket', 'siswaAktif', 'siswaNonaktif', 'akunTanpaLogin', 'akunAkses'));
    }
}

