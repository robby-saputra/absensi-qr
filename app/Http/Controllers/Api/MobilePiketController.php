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

class MobilePiketController extends Controller
{
    public function dashboard($user_id)
    {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $hari = strtolower(now()->locale('id')->translatedFormat('l'));
    $tanggal = now()->toDateString();

    $teamBase = DB::table('guru_pikets')
        ->where('hari', $hari)
        ->where('aktif', 1)
        ->whereNull('deleted_at')
        ->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)
                ->orWhere('guru_pengganti_id', $user->id)
                ->orWhere('guru_pengganti2_id', $user->id);
        })
        ->orderBy('jam_mulai')
        ->first();

    $anggotaTim = collect();
    $penggantiTim = collect();
    $teamKey = null;

    if ($teamBase) {
        $teamKey = implode('|', [
            $teamBase->tahun_ajaran_id ?? 'aktif',
            strtolower((string) $teamBase->hari),
            $teamBase->jam_mulai ?: '-',
            $teamBase->jam_selesai ?: '-',
        ]);

        $anggotaTim = DB::table('guru_pikets as gp')
            ->join('users as u', 'u.id', '=', 'gp.guru_id')
            ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
            ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
            ->where('gp.hari', $teamBase->hari)
            ->where('gp.jam_mulai', $teamBase->jam_mulai)
            ->where('gp.jam_selesai', $teamBase->jam_selesai)
            ->where('gp.aktif', 1)
            ->whereNull('gp.deleted_at')
            ->when($teamBase->tahun_ajaran_id ?? null, fn ($query) => $query->where('gp.tahun_ajaran_id', $teamBase->tahun_ajaran_id))
            ->select('gp.id', 'gp.status', 'u.nama as nama', 'g1.nama as guru_pengganti', 'g2.nama as guru_pengganti2')
            ->orderBy('u.nama')
            ->get();

        $penggantiTim = $anggotaTim
            ->flatMap(fn ($anggota) => [$anggota->guru_pengganti, $anggota->guru_pengganti2])
            ->filter()
            ->unique()
            ->values();
    }

    $qrMasuk = QrCode::whereDate('tanggal', $tanggal)
        ->where('tipe', 'masuk')
        ->when($teamKey && Schema::hasColumn('qr_codes', 'guru_piket_team_key'), fn ($query) => $query->where('guru_piket_team_key', $teamKey))
        ->latest('id')
        ->first();

    $qrPulang = QrCode::whereDate('tanggal', $tanggal)
        ->where('tipe', 'pulang')
        ->when($teamKey && Schema::hasColumn('qr_codes', 'guru_piket_team_key'), fn ($query) => $query->where('guru_piket_team_key', $teamKey))
        ->latest('id')
        ->first();

    $absensiHariIni = DB::table('absensis')
        ->whereDate('tanggal', $tanggal)
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as masuk,
            SUM(CASE WHEN jam_pulang IS NOT NULL THEN 1 ELSE 0 END) as pulang,
            SUM(CASE WHEN status_masuk IN ("izin", "sakit") OR status_pulang IN ("izin", "sakit") THEN 1 ELSE 0 END) as izin_sakit
        ')
        ->first();

    $pengajuanMenunggu = Schema::hasTable('student_permit_requests')
        ? DB::table('student_permit_requests')->where('status', 'menunggu')->whereNull('deleted_at')->count()
        : 0;

    $libur = hariLiburSekolah($tanggal);

    return response()->json([
        'status' => 'success',
        'user' => ['id' => $user->id, 'nama' => $user->nama, 'role' => $user->role],
        'tanggal' => $tanggal,
        'hari' => ucfirst($hari),
        'is_holiday' => (bool) $libur,
        'holiday' => $libur ? ['judul' => $libur->judul, 'keterangan' => $libur->keterangan ?? null] : null,
        'team' => $teamBase ? [
            'key' => $teamKey,
            'hari' => ucfirst($teamBase->hari),
            'jam_mulai' => substr((string) $teamBase->jam_mulai, 0, 5),
            'jam_selesai' => substr((string) $teamBase->jam_selesai, 0, 5),
            'anggota' => $anggotaTim->map(fn ($item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'status' => $item->status,
            ])->values(),
            'pengganti' => $penggantiTim,
        ] : null,
        'qr' => [
            'masuk' => $qrMasuk ? [
                'id' => $qrMasuk->id,
                'token' => $qrMasuk->token,
                'expires_at' => optional($qrMasuk->expires_at)->format('H:i'),
            ] : null,
            'pulang' => $qrPulang ? [
                'id' => $qrPulang->id,
                'token' => $qrPulang->token,
                'expires_at' => optional($qrPulang->expires_at)->format('H:i'),
            ] : null,
        ],
        'summary' => [
            'absensi_total' => (int) ($absensiHariIni->total ?? 0),
            'masuk' => (int) ($absensiHariIni->masuk ?? 0),
            'pulang' => (int) ($absensiHariIni->pulang ?? 0),
            'izin_sakit' => (int) ($absensiHariIni->izin_sakit ?? 0),
            'pengajuan_menunggu' => (int) $pengajuanMenunggu,
        ],
    ]);
    }

    public function absensi(Request $request, $user_id)
    {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $tanggal = $request->get('tanggal', now()->toDateString());

    $rows = DB::table('users as s')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->leftJoin('absensis as a', function ($join) use ($tanggal) {
            $join->on('a.id_siswa', '=', 's.id')
                ->whereDate('a.tanggal', $tanggal);
        })
        ->where('s.role', 'siswa')
        ->select(
            's.id as siswa_id',
            's.nama',
            's.nis',
            'k.nama_kelas',
            'a.jam_masuk',
            'a.jam_pulang',
            'a.status_masuk',
            'a.status_pulang',
            'a.catatan_piket'
        )
        ->orderBy('k.nama_kelas')
        ->orderBy('s.nama')
        ->limit(300)
        ->get();

    return response()->json([
        'status' => 'success',
        'tanggal' => $tanggal,
        'data' => $rows,
    ]);
    }

    public function riwayat(Request $request, $user_id)
    {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $rows = DB::table('absensis as a')
        ->join('users as s', 's.id', '=', 'a.id_siswa')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->select('a.tanggal', 's.nama', 's.nis', 'k.nama_kelas', 'a.jam_masuk', 'a.jam_pulang', 'a.status_masuk', 'a.status_pulang')
        ->orderByDesc('a.tanggal')
        ->orderBy('k.nama_kelas')
        ->orderBy('s.nama')
        ->limit(120)
        ->get();

    return response()->json(['status' => 'success', 'data' => $rows]);
    }

    public function jadwal($user_id)
    {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $rows = DB::table('guru_pikets as gp')
        ->join('users as g', 'g.id', '=', 'gp.guru_id')
        ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
        ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
        ->whereNull('gp.deleted_at')
        ->select(
            'gp.id',
            'gp.hari',
            'gp.jam_mulai',
            'gp.jam_selesai',
            'gp.status',
            'gp.aktif',
            'g.nama as guru_utama',
            'g1.nama as guru_pengganti',
            'g2.nama as guru_pengganti2'
        )
        ->orderBy('gp.hari')
        ->orderBy('gp.jam_mulai')
        ->get();

    return response()->json(['status' => 'success', 'data' => $rows]);
    }

    public function pengajuan(Request $request, $user_id)
    {
    $user = User::find($user_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan'], 404);
    }

    $tanggal = $request->get('tanggal', now()->toDateString());

    $rows = DB::table('student_permit_requests as p')
        ->join('users as s', 's.id', '=', 'p.siswa_id')
        ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
        ->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')
        ->whereNull('p.deleted_at')
        ->whereDate('p.tanggal_mulai', '<=', $tanggal)
        ->whereDate('p.tanggal_selesai', '>=', $tanggal)
        ->select('p.*', 's.nama as nama_siswa', 'k.nama_kelas', 'r.nama as reviewer')
        ->latest('p.id')
        ->get();

    return response()->json([
        'status' => 'success',
        'tanggal' => $tanggal,
        'data' => $rows,
    ]);
    }

    public function reviewPengajuan(Request $request, $id)
    {
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'status' => 'required|in:disetujui,ditolak',
        'catatan_review' => 'nullable|string|max:1000',
    ]);

    $pengajuan = DB::table('student_permit_requests')->where('id', $id)->whereNull('deleted_at')->first();
    if (! $pengajuan) {
        return response()->json(['status' => 'error', 'message' => 'Pengajuan tidak ditemukan'], 404);
    }

    $result = prosesReviewPengajuanSiswa((int) $id, $request->status, $request->catatan_review, $request);

    return response()->json([
        'status' => 'success',
        'message' => 'Pengajuan berhasil direview.',
        'result' => $result,
    ]);
    }
}
