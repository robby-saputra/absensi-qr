<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AttendanceSettingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MobileRoleController extends Controller
{
    public function context($user_id)
    {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $hari = strtolower(now()->locale('id')->translatedFormat('l'));
    $isWali = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
    $isGuruMapel = DB::table('jadwal_pelajarans')
        ->whereNull('deleted_at')
        ->where('guru_id', $user->id)
        ->exists();
    $isGuruMapelHariIni = DB::table('jadwal_pelajarans')
        ->whereNull('deleted_at')
        ->whereRaw('LOWER(hari) = ?', [$hari])
        ->where('guru_id', $user->id)
        ->exists();
    $isGuruPiket = DB::table('guru_pikets')
        ->where('aktif', 1)
        ->whereNull('deleted_at')
        ->where('guru_id', $user->id)
        ->exists();
    $isGuruPiketHariIni = DB::table('guru_pikets')
        ->where('aktif', 1)
        ->whereNull('deleted_at')
        ->where('hari', $hari)
        ->where('guru_id', $user->id)
        ->exists();

    $features = collect();
    if ($user->role === 'admin') {
        $features->push(['title' => 'Superadmin', 'subtitle' => 'Kelola seluruh data dan rekap.', 'path' => '/dashboard/admin', 'icon' => 'admin']);
    }
    if ($user->role === 'piket' || $isGuruPiket) {
        $features->push(['title' => 'Guru Piket', 'subtitle' => $isGuruPiketHariIni ? 'Anda bertugas piket hari ini.' : 'Akses jadwal dan rekap piket.', 'path' => '/dashboard/piket', 'icon' => 'piket']);
    }
    if ($user->role === 'guru' && $isGuruMapel) {
        $features->push(['title' => 'Guru Mapel', 'subtitle' => $isGuruMapelHariIni ? 'Ada jadwal mengajar hari ini.' : 'Lihat jadwal, absen mapel, dan rekap.', 'path' => '/dashboard/guru', 'icon' => 'guru']);
    }
    if ($user->role === 'guru' && $isWali) {
        $features->push(['title' => 'Wali Kelas', 'subtitle' => 'Pantau kelas, absensi, dan siswa rawan.', 'path' => '/dashboard/wali', 'icon' => 'wali']);
    }
    if ($user->role === 'siswa') {
        $features->push(['title' => 'Siswa', 'subtitle' => 'Scan QR, izin/sakit, dan riwayat absensi.', 'path' => null, 'icon' => 'siswa']);
    }

    return response()->json([
        'status' => 'success',
        'user' => ['id' => $user->id, 'nama' => $user->nama, 'role' => $user->role],
        'context' => [
            'is_wali' => $isWali,
            'is_guru_mapel' => $isGuruMapel,
            'is_guru_mapel_hari_ini' => $isGuruMapelHariIni,
            'is_guru_piket' => $isGuruPiket,
            'is_guru_piket_hari_ini' => $isGuruPiketHariIni,
        ],
        'features' => $features->values(),
    ]);
    }
}
