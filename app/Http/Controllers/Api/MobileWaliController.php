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

class MobileWaliController extends Controller
{
    public function dashboard(Request $request, $user_id)
    {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $wali = DB::table('kelas as k')
        ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
        ->where('k.wali_kelas_id', $user->id)
        ->select('k.id', 'k.nama_kelas', 'j.nama_jurusan')
        ->first();

    if (! $wali) {
        return response()->json(['status' => 'error', 'message' => 'Akses wali kelas tidak ditemukan'], 403);
    }

    $tanggal = $request->get('tanggal', now()->toDateString());
    $awal30Hari = now()->subDays(30)->toDateString();

    $siswa = DB::table('users as s')
        ->leftJoin('absensis as a', function ($join) use ($tanggal) {
            $join->on('a.id_siswa', '=', 's.id')
                ->whereDate('a.tanggal', $tanggal);
        })
        ->where('s.role', 'siswa')
        ->where('s.kelas_id', $wali->id)
        ->select(
            's.id',
            's.nama',
            's.nis',
            's.username',
            's.nama_ortu',
            's.no_ortu',
            'a.tanggal',
            'a.jam_masuk',
            'a.jam_pulang',
            'a.status_masuk',
            'a.status_pulang'
        )
        ->orderBy('s.nama')
        ->get();

    $absensi = DB::table('absensis as a')
        ->join('users as s', 's.id', '=', 'a.id_siswa')
        ->where('s.kelas_id', $wali->id)
        ->whereDate('a.tanggal', '>=', $awal30Hari)
        ->select(
            'a.id',
            'a.tanggal',
            'a.jam_masuk',
            'a.jam_pulang',
            'a.status_masuk',
            'a.status_pulang',
            's.id as siswa_id',
            's.nama',
            's.nis'
        )
        ->orderByDesc('a.tanggal')
        ->orderBy('s.nama')
        ->limit(300)
        ->get();

    $ringkasan = [
        'total_siswa' => $siswa->count(),
        'hadir_hari_ini' => $siswa->filter(fn ($row) => $row->jam_masuk && ! in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count(),
        'telat_hari_ini' => $siswa->where('status_masuk', 'telat')->count(),
        'belum_absen' => $siswa->filter(fn ($row) => ! $row->jam_masuk && ! in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count(),
        'izin_sakit_hari_ini' => $siswa->filter(fn ($row) => in_array($row->status_masuk, ['izin', 'sakit']) || in_array($row->status_pulang, ['izin', 'sakit']))->count(),
        'alfa_hari_ini' => $siswa->filter(fn ($row) => in_array($row->status_masuk, ['alfa', 'alpa']) || in_array($row->status_pulang, ['alfa', 'alpa']))->count(),
        'hadir_30_hari' => $absensi->filter(fn ($row) => $row->jam_masuk && ! in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count(),
        'telat_30_hari' => $absensi->where('status_masuk', 'telat')->count(),
        'alfa_30_hari' => $absensi->filter(fn ($row) => in_array($row->status_masuk, ['alfa', 'alpa']) || in_array($row->status_pulang, ['alfa', 'alpa']))->count(),
    ];

    $rawan = DB::table('absensis as a')
        ->join('users as s', 's.id', '=', 'a.id_siswa')
        ->where('s.kelas_id', $wali->id)
        ->whereDate('a.tanggal', '>=', $awal30Hari)
        ->where(function ($query) {
            $query->whereIn('a.status_masuk', ['telat', 'izin', 'sakit', 'alfa', 'alpa'])
                ->orWhereIn('a.status_pulang', ['izin', 'sakit', 'alfa', 'alpa', 'pulang_cepat']);
        })
        ->select(
            's.id',
            's.nama',
            's.nis',
            DB::raw('COUNT(*) as total_temuan'),
            DB::raw("SUM(CASE WHEN a.status_masuk = 'telat' THEN 1 ELSE 0 END) as telat"),
            DB::raw("SUM(CASE WHEN a.status_masuk IN ('alfa','alpa') OR a.status_pulang IN ('alfa','alpa') THEN 1 ELSE 0 END) as alfa")
        )
        ->groupBy('s.id', 's.nama', 's.nis')
        ->orderByDesc('total_temuan')
        ->limit(10)
        ->get();

    return response()->json([
        'status' => 'success',
        'tanggal' => $tanggal,
        'wali' => $wali,
        'ringkasan' => $ringkasan,
        'siswa' => $siswa,
        'absensi' => $absensi,
        'rawan' => $rawan,
    ]);
    }
}
