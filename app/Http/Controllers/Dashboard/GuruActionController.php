<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GuruActionController extends Controller
{
    public function viewAbsensi(Request $request, $siswaId)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());

        $siswa = siswaAktifQuery()->findOrFail($siswaId);

        $bolehAkses = DB::table('jadwal_pelajarans')
            ->where('kelas_id', $siswa->kelas_id)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->exists();

        if (! $bolehAkses) {
            abort(403);
        }

        $absensi = DB::table('absensis')
            ->where('id_siswa', $siswa->id)
            ->whereDate('tanggal', $tanggal)
            ->whereNull('deleted_at')
            ->first();

        $kelas = DB::table('kelas')->where('id', $siswa->kelas_id)->first();
        $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
        $isGuruPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $user->id)
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->exists();
        return view('dashboard.guru_absensi_view', compact(
            'user',
            'siswa',
            'kelas',
            'absensi',
            'tanggal',
            'isWaliKelas',
            'isGuruPiketHariIni'
        ));
    }

    public function viewAbsensiMapel(Request $request, $jadwalId, $siswaId)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->where('j.id', $jadwalId)
            ->whereNull('j.deleted_at')
            ->where('j.guru_id', $user->id)
            ->select('j.*', 'k.nama_kelas', 'm.nama_mapel')
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        $siswa = User::where('role', 'siswa')
            ->where('kelas_id', $jadwal->kelas_id)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->findOrFail($siswaId);

        $absensiMapel = DB::table('absensi_mapels')
            ->where('jadwal_id', $jadwalId)
            ->where('siswa_id', $siswaId)
            ->whereDate('tanggal', $tanggal)
            ->whereNull('deleted_at')
            ->first();

        $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
        $isGuruPiketHariIni = DB::table('guru_pikets')->where('guru_id', $user->id)->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->whereNull('deleted_at')->exists();
        return view('dashboard.guru_absensi_mapel_view', compact('user', 'jadwal', 'siswa', 'absensiMapel', 'tanggal', 'isWaliKelas', 'isGuruPiketHariIni'));
    }

    public function editAbsensiMapel(Request $request, $jadwalId, $siswaId)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->where('j.id', $jadwalId)
            ->whereNull('j.deleted_at')
            ->where('j.guru_id', $user->id)
            ->select('j.*', 'k.nama_kelas', 'm.nama_mapel')
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        if (absensiTerkunciUntukNonAdmin('mapel', $tanggal, (int) $jadwalId, null)) {
            return redirect('/dashboard/guru/absensi-mapel/'.$jadwalId.'/'.$siswaId.'/view?tanggal='.$tanggal)
                ->with('error', pesanAbsensiTerkunciOtomatis());
        }

        $siswa = siswaAktifQuery()->where('kelas_id', $jadwal->kelas_id)->findOrFail($siswaId);
        $absensiMapel = DB::table('absensi_mapels')->where('jadwal_id', $jadwalId)->where('siswa_id', $siswaId)->whereDate('tanggal', $tanggal)->whereNull('deleted_at')->first();
        $absensiHarian = DB::table('absensis')
            ->where('id_siswa', $siswaId)
            ->whereDate('tanggal', $tanggal)
            ->whereNull('deleted_at')
            ->first();

        $statusHarian = $absensiHarian
            ? (in_array($absensiHarian->status_masuk, ['izin', 'sakit', 'alfa', 'alpa'])
                ? $absensiHarian->status_masuk
                : (in_array($absensiHarian->status_pulang, ['izin', 'sakit', 'alfa', 'alpa']) ? $absensiHarian->status_pulang : null))
            : AttendanceSettingService::statusDefaultAlfa();

        if ($statusHarian || ! $absensiHarian?->jam_masuk) {
            return redirect('/dashboard/guru/absensi-mapel/'.$jadwalId.'/'.$siswaId.'/view?tanggal='.$tanggal)
                ->with('error', 'Siswa tidak hadir pada absensi harian guru piket, absen mapel tidak bisa diedit.');
        }

        $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
        $isGuruPiketHariIni = DB::table('guru_pikets')->where('guru_id', $user->id)->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->whereNull('deleted_at')->exists();
        return view('dashboard.guru_absensi_mapel_edit', compact('user', 'jadwal', 'siswa', 'absensiMapel', 'tanggal', 'isWaliKelas', 'isGuruPiketHariIni'));
    }

    public function updateAbsensiMapel(Request $request, $jadwalId, $siswaId)
    {
        $user = session('user');
        $request->validate([
            'tanggal' => 'required|date',
            'jam_scan' => 'nullable',
            'status' => 'required|string|max:50',
            'catatan_guru' => 'nullable|string|max:1000',
        ]);

        $jadwal = DB::table('jadwal_pelajarans')
            ->where('id', $jadwalId)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        if (absensiTerkunciUntukNonAdmin('mapel', $request->tanggal, (int) $jadwalId, null)) {
            return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
                ->with('error', pesanAbsensiTerkunciOtomatis());
        }

        siswaAktifQuery()->where('kelas_id', $jadwal->kelas_id)->findOrFail($siswaId);

        $absensiHarian = DB::table('absensis')
            ->where('id_siswa', $siswaId)
            ->whereDate('tanggal', $request->tanggal)
            ->whereNull('deleted_at')
            ->first();

        $statusHarian = $absensiHarian
            ? (in_array($absensiHarian->status_masuk, ['izin', 'sakit', 'alfa', 'alpa'])
                ? $absensiHarian->status_masuk
                : (in_array($absensiHarian->status_pulang, ['izin', 'sakit', 'alfa', 'alpa']) ? $absensiHarian->status_pulang : null))
            : AttendanceSettingService::statusDefaultAlfa();

        if ($statusHarian || ! $absensiHarian?->jam_masuk) {
            return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
                ->with('error', 'Siswa tidak hadir pada absensi harian guru piket, absen mapel tidak bisa diedit.');
        }

        $existing = DB::table('absensi_mapels')
            ->where('jadwal_id', $jadwalId)
            ->where('siswa_id', $siswaId)
            ->whereDate('tanggal', $request->tanggal)
            ->whereNull('deleted_at')
            ->first();
        $payload = [
            'tahun_ajaran_id' => DB::table('tahun_ajarans')->where('aktif', true)->value('id'),
            'jam_scan' => $request->jam_scan ?: null,
            'status' => $request->status,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('absensi_mapels', 'catatan_guru')) {
            $payload['catatan_guru'] = $request->catatan_guru;
        }

        if ($existing) {
            DB::table('absensi_mapels')->where('id', $existing->id)->update($payload);
            AuditLogger::record('update', 'absensi_mapels', (int) $existing->id, 'Absensi mapel diubah guru', $existing, DB::table('absensi_mapels')->where('id', $existing->id)->first(), $request);
        } else {
            $newId = DB::table('absensi_mapels')->insertGetId($payload + [
                'jadwal_id' => $jadwalId,
                'siswa_id' => $siswaId,
                'tanggal' => $request->tanggal,
                'created_at' => now(),
            ]);
            AuditLogger::record('create', 'absensi_mapels', (int) $newId, 'Absensi mapel dibuat guru', null, DB::table('absensi_mapels')->where('id', $newId)->first(), $request);
        }

        return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
            ->with('success', 'Absen mapel siswa berhasil diperbarui.');
    }

    public function editAbsensi(Request $request, $siswaId)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());

        $siswa = siswaAktifQuery()->findOrFail($siswaId);

        $bolehAkses = DB::table('jadwal_pelajarans')
            ->where('kelas_id', $siswa->kelas_id)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->exists();

        if (! $bolehAkses) {
            abort(403);
        }

        $absensi = DB::table('absensis')
            ->where('id_siswa', $siswa->id)
            ->whereDate('tanggal', $tanggal)
            ->whereNull('deleted_at')
            ->first();

        if (absensiTerkunciUntukNonAdmin('harian', $tanggal, null, $siswa->kelas_id ? (int) $siswa->kelas_id : null)) {
            return redirect('/dashboard/guru/absensi/'.$siswaId.'/view?tanggal='.$tanggal)
                ->with('error', pesanAbsensiTerkunciOtomatis());
        }

        $kelas = DB::table('kelas')->where('id', $siswa->kelas_id)->first();
        $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
        $isGuruPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $user->id)
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->exists();
        return view('dashboard.guru_absensi_edit', compact(
            'user',
            'siswa',
            'kelas',
            'absensi',
            'tanggal',
            'isWaliKelas',
            'isGuruPiketHariIni'
        ));
    }

    public function pengajuanIzin(Request $request)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());
        $kelasIds = DB::table('jadwal_pelajarans')
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->pluck('kelas_id')
            ->unique()
            ->values();

        $pengajuan = DB::table('student_permit_requests as p')
            ->join('users as s', 's.id', '=', 'p.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')
            ->whereIn('s.kelas_id', $kelasIds)
            ->whereNull('p.deleted_at')
            ->whereDate('p.tanggal_mulai', '<=', $tanggal)
            ->whereDate('p.tanggal_selesai', '>=', $tanggal)
            ->select('p.*', 's.nama as nama_siswa', 'k.nama_kelas', 'r.nama as reviewer')
            ->latest('p.id')
            ->get();

        $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
        $isGuruPiketHariIni = DB::table('guru_pikets')->where('guru_id', $user->id)->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->whereNull('deleted_at')->exists();
        return view('dashboard.guru_pengajuan_izin', compact('user', 'pengajuan', 'tanggal', 'isWaliKelas', 'isGuruPiketHariIni'));
    }

    public function updateAbsensi(Request $request, $siswaId)
    {
        $user = session('user');

        $request->validate([
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable',
            'jam_pulang' => 'nullable',
            'status_masuk' => 'nullable|string|max:50',
            'status_pulang' => 'nullable|string|max:50',
        ]);

        $siswa = siswaAktifQuery()->findOrFail($siswaId);

        $bolehAkses = DB::table('jadwal_pelajarans')
            ->where('kelas_id', $siswa->kelas_id)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->exists();

        if (! $bolehAkses) {
            abort(403);
        }

        $statusMasuk = $request->status_masuk ?: null;
        $statusPulang = $request->status_pulang ?: null;

        if (in_array($statusMasuk, ['izin', 'sakit']) && ! $statusPulang) {
            $statusPulang = $statusMasuk;
        }

        $existing = DB::table('absensis')
            ->where('id_siswa', $siswa->id)
            ->whereDate('tanggal', $request->tanggal)
            ->whereNull('deleted_at')
            ->first();

        if (absensiTerkunciUntukNonAdmin('harian', $request->tanggal, null, $siswa->kelas_id ? (int) $siswa->kelas_id : null)) {
            return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
                ->with('error', pesanAbsensiTerkunciOtomatis());
        }

        $payload = [
            'tahun_ajaran_id' => DB::table('tahun_ajarans')->where('aktif', true)->value('id'),
            'jam_masuk' => $request->jam_masuk ?: null,
            'jam_pulang' => $request->jam_pulang ?: null,
            'status_masuk' => $statusMasuk,
            'status_pulang' => $statusPulang,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('absensis')->where('id', $existing->id)->update($payload);
            AuditLogger::record('update', 'absensis', (int) $existing->id, 'Absensi harian diubah guru mapel', $existing, DB::table('absensis')->where('id', $existing->id)->first(), $request);
        } else {
            $newId = DB::table('absensis')->insertGetId($payload + [
                'id_siswa' => $siswa->id,
                'tanggal' => $request->tanggal,
                'created_at' => now(),
            ]);
            AuditLogger::record('create', 'absensis', (int) $newId, 'Absensi harian dibuat guru mapel', null, DB::table('absensis')->where('id', $newId)->first(), $request);
        }

        $kelasSiswa = DB::table('kelas')
            ->where('id', $siswa->kelas_id)
            ->first();

        if (in_array($statusMasuk, ['izin', 'sakit']) || in_array($statusPulang, ['izin', 'sakit'])) {
            buatNotifikasi([
                'user_id' => null,
                'judul' => 'Absensi Siswa Diubah Guru Mapel',
                'pesan' => $user->nama.' mengubah absensi '.$siswa->nama.' kelas '.($kelasSiswa->nama_kelas ?? '-').' tanggal '.$request->tanggal.'. Status masuk: '.($statusMasuk ?: '-').', status pulang: '.($statusPulang ?: '-').'.',
                'status' => 'belum_dibaca',
                'kategori' => 'absensi_siswa_diubah',
                'severity' => 'info',
                'source_type' => 'absensis',
                'source_id' => $existing->id ?? ($newId ?? null),
                'payload' => [
                    'guru_mapel' => $user->nama,
                    'siswa' => $siswa->nama,
                    'kelas' => $kelasSiswa->nama_kelas ?? '-',
                    'tanggal' => $request->tanggal,
                    'status_masuk' => $statusMasuk ?: '-',
                    'status_pulang' => $statusPulang ?: '-',
                ],
            ]);
        }

        return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
            ->with('success', 'Absensi siswa berhasil diperbarui.');
    }

    public function status(Request $request, $id)
    {
        $user = session('user');

        $request->validate([
            'status' => 'required|in:normal,izin,sakit,inval',
        ]);

        $status = $request->status;

        $jadwal = DB::table('jadwal_pelajarans')
            ->where('id', $id)
            ->where('guru_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $jadwal) {
            return back()->with('error', 'Jadwal tidak ditemukan');
        }

        if ($status == 'normal') {
            DB::table('jadwal_pelajarans')
                ->where('id', $id)
                ->update([
                    'status_guru' => 'normal',
                    'alasan_tidak_hadir' => null,
                    'updated_at' => now(),
                ]);

            return back()->with('success', 'Status hadir disimpan');
        }

        DB::table('jadwal_pelajarans')
            ->where('id', $id)
            ->update([
                'status_guru' => $status,
                'alasan_tidak_hadir' => $status,
                'updated_at' => now(),
            ]);

        $data = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->where('j.id', $id)
            ->select('k.nama_kelas', 'j.jam_mulai', 'j.jam_selesai')
            ->first();

        buatNotifikasi([
            'user_id' => null,
            'judul' => 'Guru Tidak Hadir',
            'pesan' => $user->nama.' '.$status.' pada kelas '.$data->nama_kelas.' | '.$data->jam_mulai.'-'.$data->jam_selesai,
            'kategori' => 'guru_tidak_hadir',
            'severity' => 'warning',
            'source_type' => 'jadwal_pelajarans',
            'source_id' => $id,
            'payload' => [
                'guru_utama' => $user->nama,
                'alasan' => $status,
                'kelas' => $data->nama_kelas,
                'jam' => $data->jam_mulai.' - '.$data->jam_selesai,
            ],
        ]);

        return back()->with('success', 'Status guru mapel berhasil diperbarui.');
    }

    public function mulaiSesi($jadwalId)
    {
        $user = session('user');

        if ($libur = hariLiburSekolah(now()->toDateString())) {
            return back()->with('error', 'Hari ini libur: '.$libur->judul.'. Sesi absen mapel tidak bisa dimulai.');
        }

        $jadwal = DB::table('jadwal_pelajarans')
            ->where('id', $jadwalId)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        $qr = DB::table('qr_sesis')
            ->where('jadwal_id', $jadwalId)
            ->whereDate('tanggal', now()->toDateString())
            ->where('aktif', 1)
            ->first();

        if (! $qr) {
            $token = Str::random(20);

            DB::table('qr_sesis')->insert([
                'jadwal_id' => $jadwalId,
                'tanggal' => now()->toDateString(),
                'token' => $token,
                'aktif' => 1,
                'expires_at' => now()->addMinutes(AttendanceSettingService::masaAktifQr()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $qr = DB::table('qr_sesis')
                ->where('jadwal_id', $jadwalId)
                ->whereDate('tanggal', now()->toDateString())
                ->first();
        }

        $detail = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel'
            )
            ->where('j.id', $jadwalId)
            ->first();

        return view('dashboard.guru_qr', compact(
            'user',
            'qr',
            'detail'
        ));
    }
}
