<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Models\User;
use App\Services\ActiveDutyTeacherResolver;
use App\Services\AttendanceAuditService;
use App\Services\AttendanceSettingService;
use App\Services\DutyTeacherAssignmentService;
use App\Services\DutyTeacherAttendanceService;
use App\Services\FinalizeDutyTeacherStatusService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Controller ini menyiapkan dashboard guru piket, QR harian, dan pengelolaan absensi harian siswa.
class PiketDashboardController extends Controller
{
    // Menampilkan dashboard piket berdasarkan jadwal piket aktif, status konfirmasi, dan filter tanggal.
    public function index(Request $request, DutyTeacherAttendanceService $dutyAttendance, DutyTeacherAssignmentService $assignments)
    {
        $user = session('user');

        $hariSekarang = strtolower(now()->locale('id')->translatedFormat('l'));

        // Variabel awal disiapkan agar view tetap aman meskipun guru tidak punya jadwal piket hari ini.
        $jadwalPiketHariIni = null;
        $statusHarianPiketLogin = null;
        $currentStatusPiketLogin = DutyTeacherAttendanceService::BELUM_KONFIRMASI;
        $hasConfirmedPiketToday = false;
        $statusHarianGuruUtama = null;
        $namaGuruUtamaDigantikan = null;
        $replacementAssignmentLogin = null;
        $namaPenggantiSebelumnya = null;
        $dutyStateLogin = null;
        $statusTugasLabel = 'Belum Konfirmasi';
        $posisiPiketLabel = 'Bukan petugas aktif';
        $statusQrLabel = 'QR belum aktif';
        $alasanQrTidakAktif = null;
        $isPastDutyCutoff = $assignments->isPastCutoff(now('Asia/Jakarta'));
        $dutyResetByAdmin = false;
        $punyaAksesGuruPiket = $user->role === 'piket';

        // Jika role guru membuka halaman piket, sistem cek dulu apakah ia punya tugas piket aktif.
        if ($user->role === 'guru') {
            $punyaAksesGuruPiket = DB::table('guru_pikets')
                ->where('aktif', 1)
                ->whereNull('deleted_at')
                ->where(function ($query) use ($user) {
                    $query->where('guru_id', $user->id)
                        ->orWhere('guru_pengganti_id', $user->id)
                        ->orWhereExists(function ($sub) use ($user) {
                            $sub->selectRaw('1')->from('guru_piket_replacements as r')->whereColumn('r.guru_piket_id', 'guru_pikets.id')
                                ->where('r.guru_pengganti_id', $user->id)->whereDate('r.tanggal', now()->toDateString())->whereNull('r.deleted_at');
                        });
                })
                ->exists();
            $infoLiburHariIni = infoLiburHariIni('guru');

            // Mengambil jadwal piket hari ini yang cocok dengan guru utama, pengganti, atau rantai pengganti.
            $jadwalPiketHariIni = DB::table('guru_pikets')
                ->where(function ($query) use ($user) {
                    $query->where('guru_id', $user->id)
                        ->orWhere('guru_pengganti_id', $user->id)
                        ->orWhereExists(function ($sub) use ($user) {
                            $sub->selectRaw('1')->from('guru_piket_replacements as r')->whereColumn('r.guru_piket_id', 'guru_pikets.id')
                                ->where('r.guru_pengganti_id', $user->id)->whereDate('r.tanggal', now()->toDateString())->whereNull('r.deleted_at');
                        });
                })
                ->where('hari', $hariSekarang)
                ->where('aktif', 1)
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN guru_id = ? THEN 0 ELSE 1 END', [$user->id])
                ->first();

            if ($jadwalPiketHariIni) {
                // Status harian dipakai untuk menentukan apakah guru piket sudah konfirmasi bertugas.
                $replacementAssignmentLogin = DB::table('guru_piket_replacements')->where('guru_piket_id', $jadwalPiketHariIni->id)
                    ->where('guru_pengganti_id', $user->id)->whereDate('tanggal', now()->toDateString())->whereNull('deleted_at')->orderByDesc('urutan_penggantian')->first();
                $loginSebagaiPengganti = (int) ($jadwalPiketHariIni->guru_pengganti_id ?? 0) === (int) $user->id
                    && (int) $jadwalPiketHariIni->guru_id !== (int) $user->id || $replacementAssignmentLogin !== null;
                $statusHarianGuruUtama = $dutyAttendance->statusFor((int) $jadwalPiketHariIni->id, now()->toDateString(), (int) $jadwalPiketHariIni->guru_id);
                $statusHarianPiketLogin = $dutyAttendance->statusFor((int) $jadwalPiketHariIni->id, now()->toDateString(), (int) $user->id);
                $currentStatusPiketLogin = $dutyAttendance->effectiveStatus($statusHarianPiketLogin, now('Asia/Jakarta')->toDateString(), $jadwalPiketHariIni);
                $dutyResetByAdmin = $currentStatusPiketLogin === DutyTeacherAttendanceService::MENUNGGU_VERIFIKASI_ULANG;
                $hasConfirmedPiketToday = $dutyAttendance->hasConfirmed($statusHarianPiketLogin);
                $jadwalPiketHariIni->status_harian = $currentStatusPiketLogin;
                $dutyStateLogin = $dutyAttendance->buildDutyState($jadwalPiketHariIni, now('Asia/Jakarta')->toDateString());
                $jadwalPiketHariIni->status = $dutyAttendance->statusLabel($currentStatusPiketLogin, $statusHarianPiketLogin?->sumber);
                $statusTugasLabel = $jadwalPiketHariIni->status;
                if ($loginSebagaiPengganti) {
                    $namaGuruUtamaDigantikan = DB::table('users')->where('id', $jadwalPiketHariIni->guru_id)->value('nama');
                    if ($replacementAssignmentLogin?->menggantikan_replacement_id) {
                        $namaPenggantiSebelumnya = DB::table('guru_piket_replacements as r')->join('users as u', 'u.id', '=', 'r.guru_pengganti_id')
                            ->where('r.id', $replacementAssignmentLogin->menggantikan_replacement_id)->value('u.nama');
                    }
                }
            }

            if (! $punyaAksesGuruPiket) {
                abort(403, 'Anda tidak memiliki akses guru piket aktif.');
            }
        }

        $tipe = $request->get('tipe', 'masuk');
        $isGuruPiketPengganti = ($user->role ?? null) === 'guru'
            && $jadwalPiketHariIni
            && ((int) ($jadwalPiketHariIni->guru_pengganti_id ?? 0) === (int) $user->id || $replacementAssignmentLogin !== null)
            && (int) ($jadwalPiketHariIni->guru_id ?? 0) !== (int) $user->id;

        if (($user->role ?? null) === 'piket') {
            $posisiPiketLabel = 'Operator Guru Piket';
        } elseif ($isGuruPiketPengganti && $dutyStateLogin?->active_teacher_id === (int) $user->id) {
            $posisiPiketLabel = 'Guru Pengganti Aktif';
        } elseif ($isGuruPiketPengganti) {
            $posisiPiketLabel = 'Calon Guru Pengganti';
        } elseif ($jadwalPiketHariIni) {
            $posisiPiketLabel = 'Guru Piket Utama';
        }

        $guruPiketPenggantiAktif = $isGuruPiketPengganti
            && $dutyAttendance->isReplacementActive($statusHarianGuruUtama);

        $guruPiketTidakHadir = ($user->role ?? null) === 'guru'
            && $jadwalPiketHariIni
            && ! $isGuruPiketPengganti
            && in_array($jadwalPiketHariIni->status, ['Izin', 'Sakit'], true);

        $timPiketHariIni = DB::table('guru_pikets as gp')
            ->join('users as u', 'u.id', '=', 'gp.guru_id')
            ->where('gp.hari', $hariSekarang)
            ->where('gp.aktif', 1)
            ->whereNull('gp.deleted_at')
            ->when($user->role === 'guru', function ($query) use ($user) {
                $query->where(function ($where) use ($user) {
                    $where->where('gp.guru_id', $user->id)
                        ->orWhere('gp.guru_pengganti_id', $user->id)
                        ->orWhereExists(function ($sub) use ($user) {
                            $sub->selectRaw('1')->from('guru_piket_replacements as r')->whereColumn('r.guru_piket_id', 'gp.id')
                                ->where('r.guru_pengganti_id', $user->id)->whereDate('r.tanggal', now('Asia/Jakarta')->toDateString())
                                ->whereIn('r.status_penugasan', ['menunggu_konfirmasi', 'aktif'])->whereNull('r.deleted_at');
                        });
                });
            })
            ->select('gp.*', 'u.nama as guru_utama')
            ->orderBy('gp.jam_mulai')
            ->get();

        $statusHarianTim = DB::table('guru_piket_statuses')->whereDate('tanggal', now()->toDateString())
            ->whereNull('deleted_at')
            ->whereIn('guru_piket_id', $timPiketHariIni->pluck('id'))
            ->get()
            ->keyBy(fn ($status) => $dutyAttendance->statusKey((int) $status->guru_piket_id, (int) $status->guru_id));
        $timPiketHariIni->each(function ($jadwal) use ($statusHarianTim, $dutyAttendance) {
            $state = $dutyAttendance->buildDutyState($jadwal, now('Asia/Jakarta')->toDateString(), $statusHarianTim);
            $jadwal->duty_state = $state;
            $jadwal->status_harian = $state->primary_effective_status;
            $jadwal->status = $state->primary_status_label;
        });

        $teamBase = $timPiketHariIni->first();
        $teamKey = $teamBase
            ? implode('|', [
                $teamBase->tahun_ajaran_id ?? 'aktif',
                strtolower((string) $teamBase->hari),
                $teamBase->jam_mulai ?: '-',
                $teamBase->jam_selesai ?: '-',
            ])
            : null;

        $anggotaTimPiket = $teamBase
            ? DB::table('guru_pikets as gp')
                ->join('users as u', 'u.id', '=', 'gp.guru_id')
                ->where('gp.hari', $teamBase->hari)
                ->where('gp.jam_mulai', $teamBase->jam_mulai)
                ->where('gp.jam_selesai', $teamBase->jam_selesai)
                ->where('gp.aktif', 1)
                ->whereNull('gp.deleted_at')
                ->when($teamBase->tahun_ajaran_id ?? null, fn ($query) => $query->where('gp.tahun_ajaran_id', $teamBase->tahun_ajaran_id))
                ->select('gp.*', 'u.nama as guru_utama')
                ->orderBy('u.nama')
                ->get()
            : collect();

        $activeDutyAssignment = ($user->role ?? null) === 'guru'
            ? app(ActiveDutyTeacherResolver::class)->resolve($user, now('Asia/Jakarta')->toDateString())
            : null;
        $izinOperasionalPiket = ($user->role ?? null) === 'piket'
            ? ['can_view_attendance' => true, 'can_manage_attendance' => true, 'can_manage_qr' => true]
            : [
                'can_view_attendance' => (bool) ($activeDutyAssignment?->can_view_attendance ?? false),
                'can_manage_attendance' => (bool) ($activeDutyAssignment?->can_manage_attendance ?? false),
                'can_manage_qr' => (bool) ($activeDutyAssignment?->can_manage_qr ?? false),
            ];
        $dutyStateQr = $dutyStateLogin;
        if (! $dutyStateQr && $teamBase) {
            $dutyStateQr = $dutyAttendance->buildDutyState($teamBase, now('Asia/Jakarta')->toDateString());
        }
        $liburQr = infoLiburHariIni('piket')->first();
        $qrAvailability = $dutyAttendance->resolveQrAvailability($dutyStateQr, $user, $liburQr, now('Asia/Jakarta'));
        $bolehKelolaQrPiket = (($user->role ?? null) === 'piket' || $izinOperasionalPiket['can_manage_qr'])
            && $qrAvailability->can_manage;
        $alasanQrTidakAktif = $qrAvailability->reason;
        if (! $bolehKelolaQrPiket && ! $alasanQrTidakAktif && ! $izinOperasionalPiket['can_manage_qr']) {
            $alasanQrTidakAktif = match ($currentStatusPiketLogin) {
                DutyTeacherAttendanceService::BELUM_KONFIRMASI => 'Silakan konfirmasi status Hadir terlebih dahulu sebelum mengelola QR.',
                DutyTeacherAttendanceService::IZIN, DutyTeacherAttendanceService::SAKIT => 'QR tidak aktif karena pengguna izin atau sakit.',
                default => 'Anda bukan petugas aktif pada jadwal piket ini.',
            };
        }
        $statusQrLabel = $bolehKelolaQrPiket ? 'QR dapat dikelola' : ($alasanQrTidakAktif ?: 'QR belum aktif');

        $qr = QrCode::whereDate('tanggal', now()->toDateString())
            ->where('tipe', $tipe)
            ->when(Schema::hasColumn('qr_codes', 'aktif'), fn ($query) => $query->where('aktif', true))
            ->when($teamKey && Schema::hasColumn('qr_codes', 'guru_piket_team_key'), fn ($query) => $query->where(function ($where) use ($teamKey) {
                $where->where('guru_piket_team_key', $teamKey)
                    ->orWhereNull('guru_piket_team_key');
            }))
            ->latest('id')
            ->first();

        if (! $bolehKelolaQrPiket) {
            $qr = null;
        }

        $totalSiswa = User::where('role', 'siswa')->where('aktif', 1)->whereNull('deleted_at')->count();
        $totalSiswaNonaktif = User::where('role', 'siswa')->where('aktif', 0)->whereNull('deleted_at')->count();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $semesterFilter = $request->get('semester') ?: optional($tahunAjaran->firstWhere('id', $tahunAjaranId))->semester;
        $tanggalFilter = $request->get('tanggal', now()->toDateString());
        $kelasFilter = $request->get('kelas_id');

        $absensiSiswa = DB::table('users as s')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('absensis as a', function ($join) {
                $join->on('a.id_siswa', '=', 's.id')
                    ->whereDate('a.tanggal', request()->get('tanggal', now()->toDateString()))
                    ->whereNull('a.deleted_at');
            })
            ->where('s.role', 'siswa')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->when($kelasFilter, fn ($query) => $query->where('s.kelas_id', $kelasFilter))
            ->when($tahunAjaranId, fn ($query) => $query->where(function ($where) use ($tahunAjaranId) {
                $where->where('a.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('a.tahun_ajaran_id');
            }))
            ->select(
                's.id',
                's.nama',
                's.nis',
                'k.nama_kelas',
                'a.tanggal',
                'a.jam_masuk',
                'a.jam_pulang',
                'a.status_masuk',
                'a.status_pulang',
                'a.catatan_piket'
            )
            ->orderByRaw($tipe === 'pulang'
                ? "CASE WHEN a.jam_pulang IS NOT NULL OR a.status_pulang IN ('hadir', 'telat', 'terlambat', 'izin', 'sakit', 'alfa', 'alpa') THEN 0 WHEN a.jam_masuk IS NOT NULL OR a.status_masuk IN ('hadir', 'telat', 'terlambat', 'izin', 'sakit', 'alfa', 'alpa') THEN 1 ELSE 2 END"
                : "CASE WHEN a.jam_masuk IS NOT NULL OR a.status_masuk IN ('hadir', 'telat', 'terlambat', 'izin', 'sakit', 'alfa', 'alpa') THEN 0 ELSE 1 END"
            )
            ->orderByRaw('COALESCE(a.jam_pulang, a.jam_masuk) DESC')
            ->orderBy('k.nama_kelas')
            ->orderBy('s.nama')
            ->get();
        if (! $izinOperasionalPiket['can_view_attendance']) {
            $absensiSiswa = collect();
        }
        $absensiHarianTerkunci = absensiTerkunciUntukNonAdmin('harian', $tanggalFilter, null, $kelasFilter ? (int) $kelasFilter : null);
        $belumAbsenMasuk = $absensiSiswa->filter(fn ($row) => ! $row->jam_masuk && ! in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count();
        $belumAbsenPulang = $absensiSiswa->filter(fn ($row) => $row->jam_masuk && ! $row->jam_pulang && ! in_array($row->status_pulang, ['izin', 'sakit', 'alfa', 'alpa']))->count();
        if ($belumAbsenPulang > 0) {
            buatNotifikasiRoleHarian((int) $user->id, 'piket_belum_absen_pulang', 'Siswa Belum Absen Pulang', $belumAbsenPulang.' siswa belum absen pulang pada '.$tanggalFilter.'.', ['tanggal' => $tanggalFilter, 'kelas_id' => $kelasFilter, 'total' => $belumAbsenPulang]);
        }

        $riwayatAbsensi = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->whereNull('a.deleted_at')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->when($kelasFilter, fn ($query) => $query->where('s.kelas_id', $kelasFilter))
            ->when($tahunAjaranId, fn ($query) => $query->where(function ($where) use ($tahunAjaranId) {
                $where->where('a.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('a.tahun_ajaran_id');
            }))
            ->whereDate('a.tanggal', '>=', now()->subDays(7)->toDateString())
            ->select('a.*', 's.nama', 's.nis', 'k.nama_kelas')
            ->orderByDesc('a.tanggal')
            ->orderBy('k.nama_kelas')
            ->orderBy('s.nama')
            ->get();

        $rekapJadwalPiket = DB::table('guru_pikets as gp')
            ->join('users as g', 'g.id', '=', 'gp.guru_id')
            ->whereNull('gp.deleted_at')
            ->select('gp.*', 'g.nama as guru_utama')
            ->orderBy('gp.hari')
            ->orderBy('gp.jam_mulai')
            ->get();

        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $activePiketPage = $request->get('page', 'dashboard');
        $infoLiburHariIni = infoLiburHariIni('piket');

        return view('dashboard.piket', compact(
            'user',
            'qr',
            'tipe',
            'totalSiswa',
            'totalSiswaNonaktif',
            'absensiSiswa',
            'riwayatAbsensi',
            'rekapJadwalPiket',
            'kelas',
            'kelasFilter',
            'tanggalFilter',
            'activePiketPage',
            'jadwalPiketHariIni',
            'timPiketHariIni',
            'anggotaTimPiket',
            'teamKey',
            'punyaAksesGuruPiket',
            'absensiHarianTerkunci',
            'belumAbsenMasuk',
            'belumAbsenPulang',
            'infoLiburHariIni',
            'tahunAjaran',
            'tahunAjaranId',
            'semesterFilter',
            'guruPiketTidakHadir',
            'bolehKelolaQrPiket',
            'isGuruPiketPengganti',
            'guruPiketPenggantiAktif', 'statusHarianPiketLogin', 'currentStatusPiketLogin', 'hasConfirmedPiketToday', 'statusHarianGuruUtama', 'namaGuruUtamaDigantikan', 'izinOperasionalPiket', 'replacementAssignmentLogin', 'namaPenggantiSebelumnya', 'isPastDutyCutoff', 'dutyResetByAdmin', 'dutyStateLogin', 'statusTugasLabel', 'posisiPiketLabel', 'statusQrLabel', 'alasanQrTidakAktif', 'qrAvailability'
        ));
    }

    public function pengajuanIzin(Request $request)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());
        $hari = strtolower(Carbon::parse($tanggal)->locale('id')->translatedFormat('l'));

        $bertugas = DB::table('guru_pikets')
            ->where('hari', $hari)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->exists();

        if (! $bertugas) {
            return back()->with('error', 'Anda bukan guru piket yang bertugas pada tanggal tersebut.');
        }

        $pengajuan = DB::table('student_permit_requests as p')
            ->join('users as s', 's.id', '=', 'p.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')
            ->whereNull('p.deleted_at')
            ->whereDate('p.tanggal_mulai', '<=', $tanggal)
            ->whereDate('p.tanggal_selesai', '>=', $tanggal)
            ->select('p.*', 's.nama as nama_siswa', 'k.nama_kelas', 'r.nama as reviewer')
            ->latest('p.id')
            ->get();

        return view('dashboard.piket_pengajuan_izin', compact('user', 'pengajuan', 'tanggal'));
    }

    public function reviewPengajuanIzin(Request $request, $id)
    {
        $user = session('user');
        $request->validate(['status' => 'required|in:disetujui,ditolak', 'catatan_review' => 'nullable|string']);
        $pengajuan = DB::table('student_permit_requests')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(! $pengajuan, 404);

        $hari = strtolower(Carbon::parse($pengajuan->tanggal_mulai)->locale('id')->translatedFormat('l'));
        $bertugas = DB::table('guru_pikets')
            ->where('hari', $hari)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->exists();

        abort_if(! $bertugas, 403);
        $result = prosesReviewPengajuanSiswa((int) $id, $request->status, $request->catatan_review, $request);

        return back()->with('success', 'Pengajuan berhasil direview. Absensi harian: '.$result['harian'].', absensi mapel: '.$result['mapel'].', guru diberi notifikasi: '.$result['guru_notified'].'.');
    }

    public function viewAbsensi(Request $request, $siswaId)
    {
        $this->authorizeDutyPermission('can_view_attendance');
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());

        $siswa = siswaAktifQuery()->findOrFail($siswaId);
        $kelas = DB::table('kelas')->where('id', $siswa->kelas_id)->first();
        $absensi = DB::table('absensis')
            ->where('id_siswa', $siswa->id)
            ->whereDate('tanggal', $tanggal)
            ->whereNull('deleted_at')
            ->first();

        return view('dashboard.piket_absensi_view', compact('user', 'siswa', 'kelas', 'absensi', 'tanggal'));
    }

    public function editAbsensi(Request $request, $siswaId)
    {
        $this->authorizeDutyPermission('can_manage_attendance');
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());

        $siswa = siswaAktifQuery()->findOrFail($siswaId);
        $kelas = DB::table('kelas')->where('id', $siswa->kelas_id)->first();
        $absensi = DB::table('absensis')
            ->where('id_siswa', $siswa->id)
            ->whereDate('tanggal', $tanggal)
            ->whereNull('deleted_at')
            ->first();

        if (absensiTerkunciUntukNonAdmin('harian', $tanggal, null, $siswa->kelas_id ? (int) $siswa->kelas_id : null)) {
            return redirect('/dashboard/piket/absensi/'.$siswaId.'/view?tanggal='.$tanggal)
                ->with('error', pesanAbsensiTerkunciOtomatis());
        }

        return view('dashboard.piket_absensi_edit', compact('user', 'siswa', 'kelas', 'absensi', 'tanggal'));
    }

    public function updateAbsensi(Request $request, $siswaId, DutyTeacherAssignmentService $assignments)
    {
        $login = session('user');
        if (($login->role ?? null) === 'guru') {
            $schedule = DB::table('guru_pikets')->where(function ($query) use ($login) {
                $query->where('guru_id', $login->id)->orWhere('guru_pengganti_id', $login->id)
                    ->orWhereExists(function ($sub) use ($login) {
                        $sub->selectRaw('1')->from('guru_piket_replacements as r')->whereColumn('r.guru_piket_id', 'guru_pikets.id')
                            ->where('r.guru_pengganti_id', $login->id)->whereDate('r.tanggal', now('Asia/Jakarta')->toDateString())
                            ->whereIn('r.status_penugasan', ['menunggu_konfirmasi', 'aktif'])->whereNull('r.deleted_at');
                    });
            })->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))->where('aktif', 1)->whereNull('deleted_at')->first();
            abort_unless($schedule && $assignments->permissions((int) $schedule->id, (int) $login->id, now()->toDateString())['can_manage_attendance'], 403, 'Anda bukan petugas piket aktif yang dapat mengubah absensi.');
        }
        $request->validate([
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable',
            'status_masuk' => 'nullable|string|max:50',
            'jam_pulang' => 'nullable',
            'status_pulang' => 'nullable|string|max:50',
            'catatan_piket' => 'required|string|max:1000',
        ]);

        $siswa = siswaAktifQuery()->findOrFail($siswaId);
        if (absensiTerkunciUntukNonAdmin('harian', $request->tanggal, null, $siswa->kelas_id ? (int) $siswa->kelas_id : null)) {
            return redirect('/dashboard/piket/absensi-harian?tanggal='.$request->tanggal)
                ->with('error', pesanAbsensiTerkunciOtomatis());
        }

        $statusMasuk = $request->status_masuk ?: null;
        $statusPulang = $request->status_pulang ?: null;

        if (in_array($statusMasuk, ['izin', 'sakit']) && ! $statusPulang) {
            $statusPulang = $statusMasuk;
        }

        $payload = [
            'tahun_ajaran_id' => DB::table('tahun_ajarans')->where('aktif', true)->value('id'),
            'jam_masuk' => $request->jam_masuk ?: null,
            'status_masuk' => $statusMasuk,
            'jam_pulang' => $request->jam_pulang ?: null,
            'status_pulang' => $statusPulang,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('absensis', 'catatan_piket')) {
            $payload['catatan_piket'] = $request->catatan_piket;
        }

        $existing = DB::table('absensis')
            ->where('id_siswa', $siswa->id)
            ->whereDate('tanggal', $request->tanggal)
            ->whereNull('deleted_at')
            ->first();

        if ($existing) {
            DB::table('absensis')->where('id', $existing->id)->update($payload);
        } else {
            $newId = DB::table('absensis')->insertGetId($payload + [
                'id_siswa' => $siswa->id,
                'tanggal' => $request->tanggal,
                'created_at' => now(),
            ]);
        }

        return redirect('/dashboard/piket/absensi-harian?tanggal='.$request->tanggal)
            ->with('success', 'Absensi harian siswa berhasil diperbarui.');
    }

    public function generateQr(Request $request, DutyTeacherAttendanceService $dutyAttendance)
    {
        $user = session('user');
        $this->authorizeDutyPermission('can_manage_qr');
        $activeAssignment = ($user->role ?? null) === 'guru'
            ? app(ActiveDutyTeacherResolver::class)->resolve($user, now('Asia/Jakarta')->toDateString())
            : null;

        if ($libur = hariLiburSekolah(now()->toDateString())) {
            return back()->with('error', 'Hari ini libur: '.$libur->judul.'. QR absensi harian tidak bisa dibuat.');
        }

        $request->validate([
            'tipe' => 'required|in:masuk,pulang',
        ]);

        $hariSekarang = strtolower(now()->locale('id')->translatedFormat('l'));

        $teamBase = DB::table('guru_pikets')
            ->where('hari', $hariSekarang)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->when($user->role === 'guru', fn ($query) => $query->where('id', $activeAssignment->schedule->id))
            ->orderBy('jam_mulai')
            ->first();

        if (! $teamBase) {
            return back()->with('error', 'Tim guru piket hari ini belum ditemukan. QR tim tidak bisa dibuat.');
        }

        $dutyState = $dutyAttendance->buildDutyState($teamBase, now('Asia/Jakarta')->toDateString());
        $qrAvailability = $dutyAttendance->resolveQrAvailability($dutyState, $user, hariLiburSekolah(now()->toDateString()), now('Asia/Jakarta'));
        if (! $qrAvailability->can_manage) {
            return back()->with('error', $qrAvailability->reason ?: 'QR absensi harian belum dapat dibuat.');
        }

        $anggotaTim = DB::table('guru_pikets')
            ->where('hari', $teamBase->hari)
            ->where('jam_mulai', $teamBase->jam_mulai)
            ->where('jam_selesai', $teamBase->jam_selesai)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->when($teamBase->tahun_ajaran_id ?? null, fn ($query) => $query->where('tahun_ajaran_id', $teamBase->tahun_ajaran_id))
            ->orderBy('id')
            ->get();

        $teamKey = implode('|', [
            $teamBase->tahun_ajaran_id ?? 'aktif',
            strtolower((string) $teamBase->hari),
            $teamBase->jam_mulai ?: '-',
            $teamBase->jam_selesai ?: '-',
        ]);

        $payload = [
            'tanggal' => now()->toDateString(),
            'tipe' => $request->tipe,
            'token' => Str::random(12),
            'expires_at' => now()->addMinutes(AttendanceSettingService::masaAktifQr()),
        ];

        if (Schema::hasColumn('qr_codes', 'generated_by')) {
            $payload['generated_by'] = $user->id;
        }

        if (Schema::hasColumn('qr_codes', 'guru_piket_team_key')) {
            $payload['guru_piket_team_key'] = $teamKey;
        }

        if (Schema::hasColumn('qr_codes', 'guru_piket_ids')) {
            $payload['guru_piket_ids'] = $anggotaTim->pluck('id')->implode(',');
        }

        QrCode::create($payload);

        return redirect('/dashboard/piket?tipe='.$request->tipe);
    }

    public function status(Request $request, DutyTeacherAttendanceService $dutyAttendance, DutyTeacherAssignmentService $assignments, FinalizeDutyTeacherStatusService $finalizer)
    {
        $user = session('user');
        $tanggalHariIni = now('Asia/Jakarta')->toDateString();

        $request->validate([
            'status' => 'required|in:hadir,izin,sakit',
        ]);

        if ($user->role !== 'guru') {
            return back()->with('error', 'Status kehadiran hanya untuk guru piket.');
        }

        $resolvedAssignment = app(ActiveDutyTeacherResolver::class)->resolve($user, $tanggalHariIni);
        $jadwalPiket = $resolvedAssignment?->schedule;

        if (! $jadwalPiket) {
            return back()->with('error', 'Anda tidak memiliki jadwal guru piket hari ini.');
        }

        $statusLogin = $dutyAttendance->statusFor((int) $jadwalPiket->id, $tanggalHariIni, (int) $user->id);
        $hasAdminResetBypass = $statusLogin
            && $statusLogin->status === DutyTeacherAttendanceService::BELUM_KONFIRMASI
            && $statusLogin->sumber === 'admin_reset'
            && $statusLogin->waktu_konfirmasi === null;

        $assignmentLogin = DB::table('guru_piket_replacements')->where('guru_piket_id', $jadwalPiket->id)->where('guru_pengganti_id', $user->id)
            ->whereDate('tanggal', $tanggalHariIni)->whereNull('deleted_at')->orderByDesc('urutan_penggantian')->first();
        $sebagaiPengganti = ((int) ($jadwalPiket->guru_pengganti_id ?? 0) === (int) $user->id
            && (int) $jadwalPiket->guru_id !== (int) $user->id) || $assignmentLogin !== null;
        if ($sebagaiPengganti) {
            $statusUtama = $dutyAttendance->statusFor((int) $jadwalPiket->id, $tanggalHariIni, (int) $jadwalPiket->guru_id);
            if (! $dutyAttendance->isReplacementActive($statusUtama)) {
                return back()->with('error', 'Anda belum memiliki tugas penggantian guru piket hari ini.');
            }
        }

        $hasReplacementConfirmationAccess = $sebagaiPengganti
            && $assignmentLogin
            && in_array($assignmentLogin->status_penugasan, ['menunggu_konfirmasi', 'aktif'], true)
            && ! $statusLogin?->waktu_konfirmasi;

        if ($assignments->isPastCutoff(now('Asia/Jakarta')) && ! $hasAdminResetBypass && ! $hasReplacementConfirmationAccess) {
            $finalizer->run($tanggalHariIni);

            return back()->with('error', 'Batas konfirmasi pukul 07.00 WIB telah lewat. Status yang belum dipilih otomatis ditetapkan Hadir.');
        }

        try {
            DB::transaction(function () use ($dutyAttendance, $assignments, $jadwalPiket, $tanggalHariIni, $request, $user, $sebagaiPengganti, $assignmentLogin, $hasAdminResetBypass, $statusLogin) {
                $dutyAttendance->confirm(
                    (int) $jadwalPiket->id,
                    $tanggalHariIni,
                    $request->status,
                    (int) $user->id,
                    $hasAdminResetBypass ? 'manual_setelah_reset_admin' : ($sebagaiPengganti ? 'manual_pengganti' : 'manual'),
                    $hasAdminResetBypass ? 'Verifikasi ulang setelah dibatalkan admin.' : null,
                    $sebagaiPengganti ? (($assignmentLogin?->urutan_penggantian ?? 1) === 1 ? 'pengganti_pertama' : 'pengganti_lanjutan') : 'utama',
                    $sebagaiPengganti ? (int) $jadwalPiket->guru_id : null
                );
                if (! $sebagaiPengganti && in_array($request->status, ['izin', 'sakit'], true)) {
                    $assignments->activateFirstReplacement($jadwalPiket, $tanggalHariIni, (int) $user->id, $request->status);
                }
                if ($sebagaiPengganti) {
                    $assignments->markReplacementStatus((int) $jadwalPiket->id, (int) $user->id, $tanggalHariIni, $request->status);
                }

                if ($hasAdminResetBypass) {
                    app(AttendanceAuditService::class)->record(
                        'duty_verify_after_reset',
                        'guru_piket_statuses',
                        (int) $statusLogin->id,
                        ['status' => $statusLogin],
                        [
                            'guru_piket_id' => $jadwalPiket->id,
                            'guru_id' => $user->id,
                            'tanggal' => $tanggalHariIni,
                            'status_baru' => $request->status,
                            'sumber' => 'manual_setelah_reset_admin',
                        ],
                        $request,
                        'Guru melakukan verifikasi ulang setelah reset admin.'
                    );
                }
            });
        } catch (HttpException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if (in_array($request->status, ['izin', 'sakit'])) {
            buatNotifikasi([
                'user_id' => null,
                'judul' => 'Guru Piket Tidak Hadir',
                'pesan' => $user->nama.' '.$request->status.' sebagai guru piket.',
                'kategori' => 'guru_piket_status',
                'severity' => 'warning',
                'source_type' => 'guru_pikets',
                'source_id' => $jadwalPiket->id,
                'payload' => [
                    'guru_utama' => $user->nama,
                    'alasan' => $request->status,
                    'hari' => $jadwalPiket->hari,
                    'jam' => $jadwalPiket->jam_mulai.' - '.$jadwalPiket->jam_selesai,
                ],
            ]);

            if ($sebagaiPengganti) {
                buatNotifikasi([
                    'user_id' => null,
                    'judul' => 'Pengganti piket berhalangan',
                    'pesan' => $user->nama.' yang ditugaskan sebagai pengganti piket memilih '.ucfirst($request->status).'. Segera tunjuk pengganti lanjutan.',
                    'kategori' => 'pengganti_piket_berhalangan',
                    'severity' => 'warning',
                    'source_type' => 'guru_piket_replacements',
                    'source_id' => $jadwalPiket->id,
                    'payload' => ['guru_id' => $user->id, 'tanggal' => $tanggalHariIni, 'status' => $request->status],
                ]);
            }

            return back()->with(
                'success',
                'Status guru piket berhasil disimpan.'
            );
        }

        return back()->with('success', 'Status hadir guru piket berhasil disimpan dan sudah dikunci.');
    }

    private function authorizeDutyPermission(string $permission): void
    {
        $user = session('user');
        if (($user->role ?? null) === 'piket') {
            return;
        }
        $assignment = app(ActiveDutyTeacherResolver::class)->resolve($user, now('Asia/Jakarta')->toDateString());
        if (! $assignment) {
            abort(403, 'Anda bukan petugas guru piket aktif untuk tanggal ini.');
        }
        if ($assignment->status === 'belum_konfirmasi') {
            abort(403, 'Silakan konfirmasi status Hadir terlebih dahulu sebelum mengelola QR.');
        }
        if (($assignment->assignment->status_penugasan ?? null) === 'digantikan') {
            abort(403, 'Penugasan Anda sudah dialihkan kepada guru pengganti berikutnya.');
        }
        abort_unless((bool) ($assignment->{$permission} ?? false), 403, 'Anda bukan petugas guru piket aktif untuk tanggal ini.');
    }
}
