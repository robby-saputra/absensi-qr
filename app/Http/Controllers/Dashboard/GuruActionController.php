<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActiveTeachingTeacherResolver;
use App\Services\AttendanceSettingService;
use App\Services\SubjectAttendanceTeacherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GuruActionController extends Controller
{
    private function statusGuruBertugas($jadwal): string
    {
        return $jadwal->status_guru ?: 'normal';
    }

    private function userGuruBertugas($jadwal, int $userId, string $tanggal): bool
    {
        return app(SubjectAttendanceTeacherService::class)->canManage($jadwal, $tanggal, $userId);
    }

    private function pesanGuruBelumBertugas($jadwal, int $userId): string
    {
        $statusGuru = $this->statusGuruBertugas($jadwal);

        if ((int) ($jadwal->guru_pengganti_id ?? 0) === $userId && $statusGuru === 'normal') {
            return 'Guru pengganti belum bertugas karena guru utama masih berstatus hadir.';
        }

        if ((int) ($jadwal->guru_pengganti_id ?? 0) === $userId && ($jadwal->pengganti_status ?? null) === 'tidak_hadir') {
            return 'Anda sudah melaporkan tidak bisa hadir. Jadwal ini menunggu penanganan admin.';
        }

        if ((int) ($jadwal->guru_pengganti_id ?? 0) === $userId && $statusGuru !== 'normal') {
            return 'Silakan konfirmasi terlebih dahulu di menu Status Mengajar: Saya Bertugas atau Tidak Bisa Hadir.';
        }

        if ((int) $jadwal->guru_id === $userId && $statusGuru !== 'normal') {
            return 'Guru utama sudah memilih tidak hadir. Jadwal ini sekarang menjadi tugas guru pengganti.';
        }

        return 'Anda tidak sedang bertugas pada jadwal ini.';
    }

    private function jadwalMapelSedangBerjalan($jadwal, string $tanggal): bool
    {
        $tanggalSesi = Carbon::parse($tanggal);
        $hariSesi = $tanggalSesi->copy()->locale('id')->isoFormat('dddd');
        $jamMulai = $tanggalSesi->copy()->setTimeFromTimeString((string) $jadwal->jam_mulai);
        $jamSelesai = $tanggalSesi->copy()->setTimeFromTimeString((string) $jadwal->jam_selesai);

        return $tanggalSesi->isSameDay(now())
            && Str::lower((string) $jadwal->hari) === Str::lower($hariSesi)
            && now()->betweenIncluded($jamMulai, $jamSelesai);
    }

    private function pesanJadwalMapelBelumAktif($jadwal): string
    {
        return 'Kelola absensi mapel hanya aktif pada jam pelajaran '
            .substr((string) $jadwal->jam_mulai, 0, 5)
            .' - '
            .substr((string) $jadwal->jam_selesai, 0, 5)
            .' untuk jadwal ini.';
    }

    public function viewAbsensi(Request $request, $siswaId)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());

        $siswa = siswaAktifQuery()->findOrFail($siswaId);

        $bolehAkses = DB::table('jadwal_pelajarans')
            ->where('kelas_id', $siswa->kelas_id)
            ->whereNull('deleted_at')
            ->whereNotNull('jam_ke_mulai')->whereNotNull('jumlah_jp')
            ->where(function ($query) use ($user) {
                $query->where('guru_id', $user->id)->orWhere('guru_pengganti_id', $user->id);
            })
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
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggal) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggal);
            })
            ->where('j.id', $jadwalId)
            ->whereNull('j.deleted_at')
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere('j.guru_pengganti_id', $user->id);
            })
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at'
            )
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        if (! $this->userGuruBertugas($jadwal, (int) $user->id, $tanggal)) {
            return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$tanggal)
                ->with('error', $this->pesanGuruBelumBertugas($jadwal, (int) $user->id));
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
        $isGuruPiketHariIni = DB::table('guru_pikets')->where(function ($query) use ($user) {
            $query->where('guru_id', $user->id)->orWhere('guru_pengganti_id', $user->id);
        })->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->whereNull('deleted_at')->exists();

        return view('dashboard.guru_absensi_mapel_view', compact('user', 'jadwal', 'siswa', 'absensiMapel', 'tanggal', 'isWaliKelas', 'isGuruPiketHariIni'));
    }

    public function editAbsensiMapel(Request $request, $jadwalId, $siswaId)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggal) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggal);
            })
            ->where('j.id', $jadwalId)
            ->whereNull('j.deleted_at')
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere('j.guru_pengganti_id', $user->id);
            })
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at'
            )
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        if (! $this->userGuruBertugas($jadwal, (int) $user->id, $tanggal)) {
            return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$tanggal)
                ->with('error', $this->pesanGuruBelumBertugas($jadwal, (int) $user->id));
        }

        if (! $this->jadwalMapelSedangBerjalan($jadwal, $tanggal)) {
            return redirect('/dashboard/guru/absensi-mapel/'.$jadwalId.'/'.$siswaId.'/view?tanggal='.$tanggal)
                ->with('error', $this->pesanJadwalMapelBelumAktif($jadwal));
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

        $tanggalMapel = $request->tanggal;

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggalMapel) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggalMapel);
            })
            ->where('j.id', $jadwalId)
            ->whereNull('j.deleted_at')
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere('j.guru_pengganti_id', $user->id);
            })
            ->select(
                'j.*',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at'
            )
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        if (! $this->userGuruBertugas($jadwal, (int) $user->id, $tanggalMapel)) {
            return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
                ->with('error', $this->pesanGuruBelumBertugas($jadwal, (int) $user->id));
        }

        if (! $this->jadwalMapelSedangBerjalan($jadwal, $request->tanggal)) {
            return redirect('/dashboard/guru/verifikasi-absensi?tanggal='.$request->tanggal)
                ->with('error', $this->pesanJadwalMapelBelumAktif($jadwal));
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
        ] + app(SubjectAttendanceTeacherService::class)->payload($jadwal, $request->tanggal);
        if (Schema::hasColumn('absensi_mapels', 'catatan_guru')) {
            $payload['catatan_guru'] = $request->catatan_guru;
        }

        if ($existing) {
            DB::table('absensi_mapels')->where('id', $existing->id)->update($payload);
        } else {
            $newId = DB::table('absensi_mapels')->insertGetId($payload + [
                'jadwal_id' => $jadwalId,
                'siswa_id' => $siswaId,
                'tanggal' => $request->tanggal,
                'created_at' => now(),
            ]);
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
            ->where(function ($query) use ($user) {
                $query->where('guru_id', $user->id)
                    ->orWhere('guru_pengganti_id', $user->id);
            })
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
            ->where(function ($query) use ($user) {
                $query->where('guru_id', $user->id)
                    ->orWhere('guru_pengganti_id', $user->id);
            })
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
        $hariTanggal = Carbon::parse($tanggal)->locale('id')->isoFormat('dddd');
        $kelasIds = DB::table('jadwal_pelajarans')
            ->whereNull('deleted_at')
            ->whereNotNull('jam_ke_mulai')
            ->whereNotNull('jumlah_jp')
            ->where(function ($query) use ($user) {
                $query->where('guru_id', $user->id)
                    ->orWhere('guru_pengganti_id', $user->id);
            })
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
            ->select('p.*', 's.kelas_id', 's.nama as nama_siswa', 's.nis', 'k.nama_kelas', 'r.nama as reviewer')
            ->latest('p.id')
            ->get();

        $jadwalIzin = DB::table('jadwal_pelajarans as j')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->whereIn('j.kelas_id', $kelasIds)
            ->whereRaw('LOWER(j.hari) = ?', [strtolower($hariTanggal)])
            ->whereNotNull('j.jam_ke_mulai')->whereNotNull('j.jumlah_jp')
            ->whereNull('j.deleted_at')
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)->orWhere('j.guru_pengganti_id', $user->id);
            })
            ->select('j.*', 'm.nama_mapel', DB::raw('CASE WHEN j.guru_pengganti_id = '.(int) $user->id." THEN 'guru_pengganti' ELSE 'guru_utama' END as role_mengajar"))
            ->orderBy('j.jam_mulai')->get();

        $isWaliKelas = DB::table('kelas')->where('wali_kelas_id', $user->id)->exists();
        $isGuruPiketHariIni = DB::table('guru_pikets')->where('guru_id', $user->id)->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->whereNull('deleted_at')->exists();

        return view('dashboard.guru_pengajuan_izin', compact('user', 'pengajuan', 'jadwalIzin', 'tanggal', 'isWaliKelas', 'isGuruPiketHariIni'));
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
            ->whereNotNull('jam_ke_mulai')->whereNotNull('jumlah_jp')
            ->where(function ($query) use ($user) {
                $query->where('guru_id', $user->id)->orWhere('guru_pengganti_id', $user->id);
            })
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
        } else {
            $newId = DB::table('absensis')->insertGetId($payload + [
                'id_siswa' => $siswa->id,
                'tanggal' => $request->tanggal,
                'created_at' => now(),
            ]);
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

    public function updateStatusGuru(Request $request, $jadwalId)
    {
        $user = session('user');

        $request->validate([
            'status_guru' => 'required|in:normal,izin,sakit',
        ]);

        $jadwal = DB::table('jadwal_pelajarans')
            ->where('id', $jadwalId)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        $tanggalStatus = now()->toDateString();
        $statusHarian = DB::table('jadwal_guru_statuses')
            ->where('jadwal_id', $jadwalId)
            ->whereDate('tanggal', $tanggalStatus)
            ->first();

        if ($statusHarian?->status_dipilih_at) {
            return back()->with('error', 'Status guru untuk jadwal hari ini sudah dipilih dan tidak bisa diubah lagi.');
        }

        if (now()->format('H:i') > '06:30') {
            $payload = [
                'jadwal_id' => $jadwalId,
                'tanggal' => $tanggalStatus,
                'guru_utama_id' => $jadwal->guru_id,
                'guru_pengganti_id' => $jadwal->guru_pengganti_id ?: null,
                'status_guru' => 'normal',
                'alasan_tidak_hadir' => null,
                'status_dipilih_at' => now(),
                'created_at' => $statusHarian->created_at ?? now(),
                'updated_at' => now(),
            ];

            DB::table('jadwal_guru_statuses')->updateOrInsert(
                ['jadwal_id' => $jadwalId, 'tanggal' => $tanggalStatus],
                $payload
            );

            return back()->with('error', 'Batas pilih status guru adalah pukul 06.30. Jadwal dinyatakan hadir/sedang bertugas.');
        }

        $payload = [
            'jadwal_id' => $jadwalId,
            'tanggal' => $tanggalStatus,
            'guru_utama_id' => $jadwal->guru_id,
            'guru_pengganti_id' => $jadwal->guru_pengganti_id ?: null,
            'status_guru' => $request->status_guru,
            'alasan_tidak_hadir' => $request->status_guru === 'normal' ? null : ucfirst($request->status_guru),
            'status_dipilih_at' => now(),
            'created_at' => $statusHarian->created_at ?? now(),
            'updated_at' => now(),
        ];

        DB::table('jadwal_guru_statuses')->updateOrInsert(
            ['jadwal_id' => $jadwalId, 'tanggal' => $tanggalStatus],
            $payload
        );

        if (in_array($request->status_guru, ['izin', 'sakit'], true)) {
            app(ActiveTeachingTeacherResolver::class)->ensureFirst($jadwal, $tanggalStatus, (int) $user->id, $request->status_guru);
        }

        $pesan = $request->status_guru === 'normal'
            ? 'Status berhasil dipilih: hadir.'
            : 'Status berhasil dipilih. Guru pengganti sekarang menjadi guru bertugas.';

        return back()->with('success', $pesan);
    }

    public function updateStatusGuruPengganti(Request $request, $jadwalId)
    {
        $user = session('user');

        $request->validate([
            'pengganti_status' => 'required|in:bertugas,tidak_hadir',
        ]);

        $tanggalStatus = now()->toDateString();

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->leftJoin('users as gu', 'gu.id', '=', 'j.guru_id')
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggalStatus) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggalStatus);
            })
            ->where('j.id', $jadwalId)
            ->whereNull('j.deleted_at')
            ->where('j.guru_pengganti_id', $user->id)
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'gu.nama as nama_guru_utama',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.created_at as status_created_at'
            )
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        if ($this->statusGuruBertugas($jadwal) === 'normal') {
            return back()->with('error', 'Guru utama masih berstatus hadir. Anda belum perlu konfirmasi sebagai guru pengganti.');
        }

        if ($jadwal->pengganti_status) {
            return back()->with('error', 'Status guru pengganti untuk jadwal hari ini sudah dikonfirmasi.');
        }

        $statusLabel = $request->pengganti_status === 'bertugas' ? 'Bertugas' : 'Tidak Bisa Hadir';

        DB::table('jadwal_guru_statuses')->updateOrInsert(
            ['jadwal_id' => $jadwalId, 'tanggal' => $tanggalStatus],
            [
                'jadwal_id' => $jadwalId,
                'tanggal' => $tanggalStatus,
                'guru_utama_id' => $jadwal->guru_id,
                'guru_pengganti_id' => $jadwal->guru_pengganti_id ?: null,
                'status_guru' => $this->statusGuruBertugas($jadwal),
                'alasan_tidak_hadir' => $jadwal->alasan_tidak_hadir ?? ucfirst($this->statusGuruBertugas($jadwal)),
                'status_dipilih_at' => $jadwal->status_dipilih_at ?: now(),
                'pengganti_status' => $request->pengganti_status,
                'pengganti_alasan' => $statusLabel,
                'pengganti_dipilih_at' => now(),
                'created_at' => $jadwal->status_created_at ?? now(),
                'updated_at' => now(),
            ]
        );

        app(ActiveTeachingTeacherResolver::class)->markStatus(
            (int) $jadwalId,
            (int) $user->id,
            $tanggalStatus,
            $request->pengganti_status === 'bertugas' ? 'hadir' : 'sakit'
        );

        if ($request->pengganti_status === 'tidak_hadir') {
            buatNotifikasi([
                'user_id' => null,
                'judul' => 'Guru Pengganti Tidak Bisa Hadir',
                'pesan' => $user->nama.' tidak bisa menggantikan '.$jadwal->nama_guru_utama.' untuk '.$jadwal->nama_mapel.' kelas '.$jadwal->nama_kelas.' pada '.$tanggalStatus.'.',
                'status' => 'belum_dibaca',
                'kategori' => 'guru_pengganti_tidak_hadir',
                'severity' => 'danger',
                'source_type' => 'jadwal_guru_statuses',
                'source_id' => $jadwalId,
                'payload' => [
                    'jadwal_id' => $jadwalId,
                    'tanggal' => $tanggalStatus,
                    'guru_utama' => $jadwal->nama_guru_utama,
                    'guru_pengganti' => $user->nama,
                    'mapel' => $jadwal->nama_mapel,
                    'kelas' => $jadwal->nama_kelas,
                    'jam' => substr((string) $jadwal->jam_mulai, 0, 5).' - '.substr((string) $jadwal->jam_selesai, 0, 5),
                    'status_guru_utama' => $this->statusGuruBertugas($jadwal),
                ],
            ]);

            return back()->with('error', 'Status tidak bisa hadir sudah dikirim ke admin. Jadwal menunggu penanganan admin.');
        }

        return back()->with('success', 'Konfirmasi berhasil. Anda sekarang menjadi guru bertugas untuk jadwal ini.');
    }

    public function mulaiSesi($jadwalId)
    {
        $user = session('user');

        if ($libur = hariLiburSekolah(now()->toDateString())) {
            return back()->with('error', 'Hari ini libur: '.$libur->judul.'. Sesi absen mapel tidak bisa dimulai.');
        }

        $tanggalHariIni = now()->toDateString();

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggalHariIni) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggalHariIni);
            })
            ->where('j.id', $jadwalId)
            ->whereNull('j.deleted_at')
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere('j.guru_pengganti_id', $user->id);
            })
            ->select(
                'j.*',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at'
            )
            ->first();

        if (! $jadwal) {
            abort(403);
        }

        if (! $this->userGuruBertugas($jadwal, (int) $user->id, $tanggalHariIni)) {
            return back()->with('error', $this->pesanGuruBelumBertugas($jadwal, (int) $user->id));
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
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggalHariIni) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggalHariIni);
            })
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at'
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
