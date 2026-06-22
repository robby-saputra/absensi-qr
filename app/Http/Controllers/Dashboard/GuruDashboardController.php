<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ActiveDutyTeacherResolver;
use App\Services\DutyTeacherAssignmentService;

class GuruDashboardController extends Controller
{

    public function index(Request $request, ActiveDutyTeacherResolver $dutyResolver, DutyTeacherAssignmentService $dutyAssignments)
    {        $user = session(

            'user'

        );

        $hari = now()
            ->locale(

                'id'

            )
            ->isoFormat(

                'dddd'

            );

        $tanggalHariIni = now()->toDateString();

        $jadwal = DB::table(

            'jadwal_pelajarans as j'

        )
            ->join(

                'kelas as k',

                'k.id',

                '=',

                'j.kelas_id'

            )
            ->join(

                'mapels as m',

                'm.id',

                '=',

                'j.mapel_id'

            )
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggalHariIni) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggalHariIni);
            })
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere('j.guru_pengganti_id', $user->id);
            })
            ->where(

                'j.hari',

                $hari

            )
            ->whereNotNull('j.jam_ke_mulai')
            ->whereNotNull('j.jumlah_jp')
            ->whereNull('j.deleted_at')
            ->select(

                'j.*',

                'k.nama_kelas',

                'm.nama_mapel',

                'j.keterangan',

                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),

                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at',
                DB::raw("CASE WHEN j.guru_pengganti_id = ".(int) $user->id." THEN 'guru_pengganti' ELSE 'guru_utama' END as role_mengajar")

            )
            ->orderBy(

                'j.jam_mulai'

            )
            ->get();

        $statusMengajarHariIni = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as gu', 'gu.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggalHariIni) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggalHariIni);
            })
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere('j.guru_pengganti_id', $user->id);
            })
            ->where('j.hari', $hari)
            ->whereNotNull('j.jam_ke_mulai')
            ->whereNotNull('j.jumlah_jp')
            ->whereNull('j.deleted_at')
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'gu.nama as nama_guru_utama',
                'gp.nama as nama_guru_pengganti',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at',
                DB::raw("CASE WHEN j.guru_pengganti_id = ".(int) $user->id." THEN 'guru_pengganti' ELSE 'guru_utama' END as role_mengajar")
            )
            ->orderBy('j.jam_mulai')
            ->get();

        $jamSekarang = now()->format('H:i:s');

        $tugasSaatIni = $jadwal
            ->filter(fn ($item) => $item->jam_mulai <= $jamSekarang && $item->jam_selesai >= $jamSekarang)
            ->filter(function ($item) use ($user) {
                $statusGuru = $item->status_guru ?: 'normal';

                if ($statusGuru === 'normal') {
                    return (int) $item->guru_id === (int) $user->id;
                }

                return (int) ($item->guru_pengganti_id ?? 0) === (int) $user->id
                    && ($item->pengganti_status ?? null) === 'bertugas';
            })
            ->map(function ($item) {
                return (object) [
                    'jenis' => 'Guru Mapel',
                    'detail' => trim(($item->nama_mapel ?? '-').' - '.($item->nama_kelas ?? '-')),
                    'jam_mulai' => $item->jam_mulai,
                    'jam_selesai' => $item->jam_selesai,
                    'jam_ke_mulai' => $item->jam_ke_mulai ?? null,
                    'jumlah_jp' => $item->jumlah_jp ?? null,
                    'status' => 'Sedang Bertugas',
                ];
            })
            ->values();

        $tugasPiketSaatIni = DB::table('guru_pikets as gp')
            ->leftJoin('users as gu', 'gu.id', '=', 'gp.guru_id')
            ->where('gp.hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('gp.aktif', 1)
            ->whereNull('gp.deleted_at')
            ->where('gp.jam_mulai', '<=', $jamSekarang)
            ->where('gp.jam_selesai', '>=', $jamSekarang)
            ->where('gp.guru_id', $user->id)
            ->select('gp.*', 'gu.nama as guru_utama')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'jenis' => 'Guru Piket',
                    'detail' => 'Tim piket hari ini',
                    'jam_mulai' => $item->jam_mulai,
                    'jam_selesai' => $item->jam_selesai,
                    'status' => $item->status ?: 'Sedang Bertugas',
                ];
            });

        $tugasSaatIni = $tugasSaatIni
            ->merge($tugasPiketSaatIni)
            ->sortBy('jam_mulai')
            ->values();

        $tanggalFilter = $request->get('tanggal', now()->toDateString());

        $semuaJadwalGuru = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->leftJoin('users as gu', 'gu.id', '=', 'j.guru_id')
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggalFilter) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $tanggalFilter);
            })
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere('j.guru_pengganti_id', $user->id);
            })
            ->whereNotNull('j.jam_ke_mulai')
            ->whereNotNull('j.jumlah_jp')
            ->whereNull('j.deleted_at')
            ->select(
                'j.*',
                'k.nama_kelas',
                'jr.nama_jurusan',
                'm.nama_mapel',
                'gu.nama as guru_utama',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at',
                DB::raw("CASE WHEN j.guru_pengganti_id = ".(int) $user->id." THEN 'guru_pengganti' ELSE 'guru_utama' END as role_mengajar")
            )
            ->orderBy('j.hari')
            ->orderBy('j.jam_mulai')
            ->get();

        $kelasAjarIds = $semuaJadwalGuru
            ->pluck('kelas_id')
            ->filter()
            ->unique()
            ->values();

        $absensiKelasAjar = collect();
        $absensiMapelKelasAjar = collect();
        $riwayatAbsensiKelasAjar = collect();
        $riwayatAbsensiMapelGuru = collect();
        $rekapSiswaGuru = collect();
        $rekapAbsensiMapelGuru = collect();
        $siswaNonaktifKelasAjarCount = 0;
        $liburTanggalFilter = hariLiburSekolah($tanggalFilter);
        $hariFilter = $request->get('hari');
        $bulanFilter = $request->get('bulan');
        $tahunFilter = $request->get('tahun');
        $kelasFilter = $request->get('kelas_id');
        $jurusanFilter = $request->get('jurusan_id');
        $statusHarianFilter = $request->get('status_harian');
        $jadwalFilter = $request->get('jadwal_id');
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $semesterFilter = $request->get('semester') ?: optional($tahunAjaran->firstWhere('id', $tahunAjaranId))->semester;
        $rekapHariFilter = $request->get('rekap_hari');
        $rekapJpFilter = $request->get('rekap_jadwal_id');
        $rekapKelasFilter = $request->get('rekap_kelas_id');
        $rekapMapelFilter = $request->get('rekap_mapel_id');
        $rekapPeranFilter = $request->get('rekap_peran');
        $rekapTahunAjaranFilter = $request->get('rekap_tahun_ajaran_id') ?: $tahunAjaranId;
        $kelasAjar = collect();
        $jadwalMapelFilterOptions = collect();
        $rekapJadwalGuru = collect();
        $rekapMapelOptions = collect();
        $jurusan = DB::table('jurusan')->orderBy('nama_jurusan')->get();
        $hariKalenderMengajar = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $ringkasanGuru = [
            'hadir' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alfa' => 0,
            'mapel_terisi' => 0,
            'mapel_belum' => 0,
        ];

        $semuaKelasAjarIds = $semuaJadwalGuru
            ->pluck('kelas_id')
            ->filter()
            ->unique()
            ->values();

        $jadwalTerisiTanggalIds = DB::table('absensi_mapels')
            ->whereDate('tanggal', $tanggalFilter)
            ->whereNull('deleted_at')
            ->whereIn('jadwal_id', $semuaJadwalGuru->pluck('id'))
            ->pluck('jadwal_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $jadwalVerifikasiGuruIds = $semuaJadwalGuru
            ->pluck('id')
            ->values();

        $tanggalFilterCarbon = \Carbon\Carbon::parse($tanggalFilter);
        $hariTanggalFilter = $tanggalFilterCarbon->copy()->locale('id')->isoFormat('dddd');
        $jamSekarangFilter = now();

        $jadwalMapelFilterOptions = $semuaJadwalGuru
            ->filter(fn ($item) => \Illuminate\Support\Str::lower((string) $item->hari) === \Illuminate\Support\Str::lower($hariTanggalFilter))
            ->map(function ($item) use ($tanggalFilterCarbon, $jamSekarangFilter) {
                $jamMulai = $tanggalFilterCarbon->copy()->setTimeFromTimeString((string) $item->jam_mulai);
                $jamSelesai = $tanggalFilterCarbon->copy()->setTimeFromTimeString((string) $item->jam_selesai);
                $item->sedang_berjalan = $tanggalFilterCarbon->isSameDay(now()) && $jamSekarangFilter->betweenIncluded($jamMulai, $jamSelesai);

                return $item;
            })
            ->sortBy([
                fn ($item) => empty($item->sedang_berjalan) ? 1 : 0,
                fn ($item) => $item->jam_mulai ?? '',
                fn ($item) => $item->nama_mapel ?? '',
            ])
            ->values();

        if ($kelasAjarIds->isNotEmpty()) {
            $kelasAjar = DB::table('kelas')
                ->whereIn('id', $kelasAjarIds)
                ->orderBy('nama_kelas')
                ->get();

            $filteredKelasIds = $kelasAjarIds;

            if ($kelasFilter) {
                $filteredKelasIds = $filteredKelasIds
                    ->filter(fn ($id) => (string) $id === (string) $kelasFilter)
                    ->values();
            }

            if ($jurusanFilter) {
                $kelasJurusanIds = DB::table('kelas')
                    ->where('jurusan_id', $jurusanFilter)
                    ->pluck('id');

                $filteredKelasIds = $filteredKelasIds
                    ->intersect($kelasJurusanIds)
                    ->values();
            }

            $absensiKelasAjar = DB::table('users as s')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->leftJoin('absensis as a', function ($join) {
                    $join->on('a.id_siswa', '=', 's.id')
                        ->whereDate('a.tanggal', request()->get('tanggal', now()->toDateString()))
                        ->whereNull('a.deleted_at');
                })
                ->where('s.role', 'siswa')
                ->where('s.aktif', 1)
                ->whereNull('s.deleted_at')
                ->whereIn('s.kelas_id', $filteredKelasIds)
                ->when($tahunAjaranId, fn ($query) => $query->where(function ($where) use ($tahunAjaranId) {
                    $where->where('a.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('a.tahun_ajaran_id');
                }))
                ->select(
                    'a.id as absensi_id',
                    's.id',
                    's.nama',
                    's.nis',
                    'k.nama_kelas',
                    'jr.nama_jurusan',
                    'a.tanggal',
                    'a.jam_masuk',
                    'a.jam_pulang',
                    'a.status_masuk',
                    'a.status_pulang'
                )
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->get();

            $absensiMapelKelasAjar = DB::table('jadwal_pelajarans as j')
                ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->join('users as s', function ($join) {
                    $join->on('s.kelas_id', '=', 'j.kelas_id')
                        ->where('s.role', 'siswa')
                        ->where('s.aktif', 1)
                        ->whereNull('s.deleted_at');
                })
                ->leftJoin('absensi_mapels as am', function ($join) use ($tanggalFilter) {
                    $join->on('am.jadwal_id', '=', 'j.id')
                        ->on('am.siswa_id', '=', 's.id')
                        ->whereDate('am.tanggal', $tanggalFilter)
                        ->whereNull('am.deleted_at');
                })
                ->leftJoin('absensis as ah', function ($join) use ($tanggalFilter) {
                    $join->on('ah.id_siswa', '=', 's.id')
                        ->whereDate('ah.tanggal', $tanggalFilter)
                        ->whereNull('ah.deleted_at');
                })
                ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggalFilter) {
                    $join->on('jgs.jadwal_id', '=', 'j.id')
                        ->whereDate('jgs.tanggal', $tanggalFilter);
                })
                ->whereIn('j.id', $jadwalVerifikasiGuruIds)
                ->whereIn('j.kelas_id', $filteredKelasIds)
                ->whereNull('j.deleted_at')
                ->whereNotNull('j.jam_ke_mulai')
                ->whereNotNull('j.jumlah_jp')
                ->when($jadwalFilter, fn ($query) => $query->where('j.id', $jadwalFilter))
                ->when($hariFilter, fn ($query) => $query->where('j.hari', $hariFilter))
                ->when($tahunAjaranId, fn ($query) => $query->where(function ($where) use ($tahunAjaranId) {
                    $where->where('j.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('j.tahun_ajaran_id');
                }))
                ->when($statusHarianFilter === 'izin', function ($query) {
                    $query->where(function ($where) {
                        $where->where('ah.status_masuk', 'izin')
                            ->orWhere('ah.status_pulang', 'izin');
                    });
                })
                ->when($statusHarianFilter === 'sakit', function ($query) {
                    $query->where(function ($where) {
                        $where->where('ah.status_masuk', 'sakit')
                            ->orWhere('ah.status_pulang', 'sakit');
                    });
                })
                ->when($statusHarianFilter === 'alfa' && ! $liburTanggalFilter, function ($query) {
                    $query->where(function ($where) {
                        $where->whereNull('ah.id')
                            ->orWhere('ah.status_masuk', 'alfa')
                            ->orWhere('ah.status_masuk', 'alpa')
                            ->orWhere('ah.status_pulang', 'alfa')
                            ->orWhere('ah.status_pulang', 'alpa');
                    });
                })
                ->when($statusHarianFilter === 'alfa' && $liburTanggalFilter, function ($query) {
                    $query->whereRaw('1 = 0');
                })
                ->when($statusHarianFilter === 'hadir', function ($query) {
                    $query->whereNotNull('ah.jam_masuk')
                        ->whereNotIn(DB::raw('COALESCE(ah.status_masuk, "")'), ['izin', 'sakit', 'alfa', 'alpa'])
                        ->whereNotIn(DB::raw('COALESCE(ah.status_pulang, "")'), ['izin', 'sakit', 'alfa', 'alpa']);
                })
                ->select(
                    'am.id as absensi_mapel_id',
                    'am.tanggal',
                    'am.jam_scan',
                    'am.status',
                    'am.catatan_guru',
                    'ah.jam_masuk as jam_harian_masuk',
                    'ah.status_masuk as status_harian_masuk',
                    'ah.jam_pulang as jam_harian_pulang',
                    'ah.status_pulang as status_harian_pulang',
                    'j.id as jadwal_id',
                    'j.kelas_id',
                    DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                    'j.jam_mulai',
                    'j.jam_selesai',
                    'j.jam_ke_mulai',
                    'j.jumlah_jp',
                    'j.hari',
                    'j.guru_id',
                    'j.guru_pengganti_id',
                    's.id as siswa_id',
                    's.nama',
                    's.nis',
                    'k.nama_kelas',
                    'jr.nama_jurusan',
                    'm.nama_mapel',
                    DB::raw("CASE WHEN j.guru_pengganti_id = ".(int) $user->id." THEN 'guru_pengganti' ELSE 'guru_utama' END as role_mengajar"),
                    DB::raw("
                        CASE
                            WHEN COALESCE(jgs.status_guru, 'normal') = 'normal' AND j.guru_id = ".(int) $user->id." THEN 1
                            WHEN COALESCE(jgs.status_guru, 'normal') <> 'normal' AND j.guru_pengganti_id = ".(int) $user->id." AND jgs.pengganti_status = 'bertugas' THEN 1
                            ELSE 0
                        END as boleh_kelola_mapel
                    ")
                )
                ->orderBy('k.nama_kelas')
                ->orderBy('m.nama_mapel')
                ->orderBy('s.nama')
                ->get();

            $absensiMapelKelasAjar = $absensiMapelKelasAjar
                ->sortBy([
                    fn ($row) => empty($row->absensi_mapel_id) ? 1 : 0,
                    fn ($row) => $row->nama_kelas ?? '',
                    fn ($row) => $row->nama ?? '',
                ])
                ->unique(function ($row) {
                    return 'jadwal-'.$row->jadwal_id.'-siswa-'.$row->siswa_id.'-tanggal-'.($row->tanggal ?: request()->get('tanggal', now()->toDateString()));
                })
                ->sortBy([
                    fn ($row) => $row->nama_kelas ?? '',
                    fn ($row) => $row->nama_mapel ?? '',
                    fn ($row) => empty($row->jam_harian_masuk) ? 1 : 0,
                    fn ($row) => empty($row->jam_harian_masuk) ? PHP_INT_MAX : -strtotime((string) $row->jam_harian_masuk),
                    fn ($row) => empty($row->absensi_mapel_id) ? 1 : 0,
                    fn ($row) => empty($row->jam_scan) ? PHP_INT_MAX : -strtotime((string) $row->jam_scan),
                    fn ($row) => $row->nama ?? '',
                ])
                ->values();

            if ($liburTanggalFilter) {
                $absensiMapelKelasAjar = $absensiMapelKelasAjar->map(function ($row) use ($liburTanggalFilter) {
                    $row->status_harian_masuk = $row->status_harian_masuk ?: 'libur';
                    $row->status_harian_pulang = $row->status_harian_pulang ?: 'libur';
                    $row->keterangan_libur = $liburTanggalFilter->judul;

                    return $row;
                });
            }

            $siswaNonaktifPerKelas = DB::table('users')
                ->where('role', 'siswa')
                ->where('aktif', 0)
                ->whereNull('deleted_at')
                ->whereIn('kelas_id', $filteredKelasIds)
                ->select('kelas_id', DB::raw('COUNT(*) as total'))
                ->groupBy('kelas_id')
                ->pluck('total', 'kelas_id');

            $absensiMapelKelasAjar = $absensiMapelKelasAjar->map(function ($row) use ($tanggalFilter, $siswaNonaktifPerKelas) {
                $tanggalSesi = \Carbon\Carbon::parse($tanggalFilter);
                $hariSesi = $tanggalSesi->copy()->locale('id')->isoFormat('dddd');
                $jamMulai = $tanggalSesi->copy()->setTimeFromTimeString((string) $row->jam_mulai);
                $jamSelesai = $tanggalSesi->copy()->setTimeFromTimeString((string) $row->jam_selesai);

                $row->sesi_terkunci = absensiTerkunciUntukNonAdmin('mapel', $tanggalFilter, (int) $row->jadwal_id, null);
                $row->sesi_sedang_berjalan = $tanggalSesi->isSameDay(now())
                    && \Illuminate\Support\Str::lower((string) $row->hari) === \Illuminate\Support\Str::lower($hariSesi)
                    && now()->betweenIncluded($jamMulai, $jamSelesai);
                $row->siswa_nonaktif_kelas = (int) ($siswaNonaktifPerKelas[$row->kelas_id] ?? 0);

                return $row;
            });

            $ringkasanGuru['izin'] = $absensiMapelKelasAjar->filter(fn ($row) => in_array($row->status_harian_masuk, ['izin']) || in_array($row->status_harian_pulang, ['izin']))->count();
            $ringkasanGuru['sakit'] = $absensiMapelKelasAjar->filter(fn ($row) => in_array($row->status_harian_masuk, ['sakit']) || in_array($row->status_harian_pulang, ['sakit']))->count();
            $ringkasanGuru['alfa'] = $absensiMapelKelasAjar->filter(fn ($row) => in_array($row->status_harian_masuk, ['alfa', 'alpa']) || in_array($row->status_harian_pulang, ['alfa', 'alpa']) || (! $row->jam_harian_masuk && ! in_array($row->status_harian_masuk, ['libur'])))->count();
            $ringkasanGuru['telat'] = $absensiMapelKelasAjar->filter(fn ($row) => $row->status_harian_masuk === 'telat')->count();
            $ringkasanGuru['hadir'] = $absensiMapelKelasAjar->filter(fn ($row) => $row->jam_harian_masuk && ! in_array($row->status_harian_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count();
            $ringkasanGuru['mapel_terisi'] = $absensiMapelKelasAjar->whereNotNull('absensi_mapel_id')->count();
            $ringkasanGuru['mapel_belum'] = max(0, $absensiMapelKelasAjar->count() - $ringkasanGuru['mapel_terisi']);
            $rekapAbsensiMapelGuru = $absensiMapelKelasAjar;

            $riwayatAbsensiKelasAjar = DB::table('absensis as a')
                ->join('users as s', 's.id', '=', 'a.id_siswa')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->whereNull('a.deleted_at')
                ->whereIn('s.kelas_id', $filteredKelasIds)
                ->where('s.aktif', 1)
                ->whereNull('s.deleted_at')
                ->when($tahunAjaranId, fn ($query) => $query->where(function ($where) use ($tahunAjaranId) {
                    $where->where('a.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('a.tahun_ajaran_id');
                }))
                ->when($hariFilter, function ($query) use ($hariFilter) {
                    $dayIndex = [
                        'Minggu' => 0,
                        'Senin' => 1,
                        'Selasa' => 2,
                        'Rabu' => 3,
                        'Kamis' => 4,
                        'Jumat' => 5,
                        'Sabtu' => 6,
                    ][$hariFilter] ?? null;

                    if ($dayIndex !== null) {
                        $query->whereRaw('DAYOFWEEK(a.tanggal) = ?', [$dayIndex + 1]);
                    }
                })
                ->when($bulanFilter, fn ($query) => $query->whereMonth('a.tanggal', $bulanFilter))
                ->when($tahunFilter, fn ($query) => $query->whereYear('a.tanggal', $tahunFilter))
                ->when(! $bulanFilter && ! $tahunFilter && ! $hariFilter, fn ($query) => $query->whereDate('a.tanggal', '>=', now()->subDays(7)->toDateString()))
                ->select(
                    'a.*',
                    's.nama',
                    's.nis',
                    'k.nama_kelas',
                    'jr.nama_jurusan'
                )
                ->orderByDesc('a.tanggal')
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->limit(100)
                ->get();

            $riwayatAbsensiMapelGuru = DB::table('absensi_mapels as am')
                ->join('jadwal_pelajarans as j', 'j.id', '=', 'am.jadwal_id')
                ->join('users as s', 's.id', '=', 'am.siswa_id')
                ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->leftJoin('absensis as ah', function ($join) {
                    $join->on('ah.id_siswa', '=', 's.id')
                        ->whereColumn('ah.tanggal', 'am.tanggal')
                        ->whereNull('ah.deleted_at');
                })
                ->whereNull('am.deleted_at')
                ->whereNull('j.deleted_at')
                ->whereNotNull('j.jam_ke_mulai')
                ->whereNotNull('j.jumlah_jp')
                ->where('s.aktif', 1)
                ->whereNull('s.deleted_at')
                ->where(function ($query) use ($user) {
                    $query->where('j.guru_id', $user->id)
                        ->orWhere('j.guru_pengganti_id', $user->id);
                })
                ->when($tahunAjaranId, fn ($query) => $query->where(function ($where) use ($tahunAjaranId) {
                    $where->where('am.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('am.tahun_ajaran_id');
                }))
                ->when($bulanFilter, fn ($query) => $query->whereMonth('am.tanggal', $bulanFilter))
                ->when($tahunFilter, fn ($query) => $query->whereYear('am.tanggal', $tahunFilter))
                ->when(! $bulanFilter && ! $tahunFilter, fn ($query) => $query->whereDate('am.tanggal', '>=', now()->subDays(30)->toDateString()))
                ->select(
                    'am.id', 'am.tanggal', 'am.jam_scan', 'am.status', 'am.catatan_guru',
                    'j.id as jadwal_id', 'j.hari', 'j.jam_mulai', 'j.jam_selesai', 'j.jam_ke_mulai', 'j.jumlah_jp',
                    's.id as siswa_id', 's.nama', 's.nis', 'k.nama_kelas', 'jr.nama_jurusan', 'm.nama_mapel',
                    'ah.jam_masuk as jam_harian_masuk', 'ah.status_masuk as status_harian_masuk',
                    'ah.jam_pulang as jam_harian_pulang', 'ah.status_pulang as status_harian_pulang',
                    DB::raw("CASE WHEN j.guru_pengganti_id = ".(int) $user->id." THEN 'guru_pengganti' ELSE 'guru_utama' END as role_mengajar")
                )
                ->orderByDesc('am.tanggal')
                ->orderBy('j.jam_mulai')
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->limit(250)
                ->get();
        }

        if ($semuaKelasAjarIds->isNotEmpty()) {
            $siswaNonaktifKelasAjarCount = DB::table('users')
                ->where('role', 'siswa')
                ->where('aktif', 0)
                ->whereNull('deleted_at')
                ->whereIn('kelas_id', $semuaKelasAjarIds)
                ->count();

            $rekapSiswaGuru = DB::table('users as s')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->where('s.role', 'siswa')
                ->where('s.aktif', 1)
                ->whereNull('s.deleted_at')
                ->whereIn('s.kelas_id', $semuaKelasAjarIds)
                ->select('s.*', 'k.nama_kelas', 'jr.nama_jurusan')
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->get();

            if ($rekapAbsensiMapelGuru->isEmpty()) {
                $rekapAbsensiMapelGuru = DB::table('absensi_mapels as a')
                ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
                ->join('users as s', 's.id', '=', 'a.siswa_id')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->whereNull('a.deleted_at')
                ->where('s.aktif', 1)
                ->whereNull('s.deleted_at')
                ->where(function ($query) use ($user) {
                    $query->where('j.guru_id', $user->id)
                        ->orWhere('j.guru_pengganti_id', $user->id);
                })
                ->whereNull('j.deleted_at')
                ->whereNotNull('j.jam_ke_mulai')
                ->whereNotNull('j.jumlah_jp')
                ->select('a.*', 's.nama as nama_siswa', 'k.nama_kelas', 'm.nama_mapel', 'j.hari', 'j.jam_mulai', 'j.jam_selesai', 'j.jam_ke_mulai', 'j.jumlah_jp')
                ->latest('a.tanggal')
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->limit(150)
                ->get();
            }
        }

        if ($absensiMapelKelasAjar->isNotEmpty()) {
            $izinSakit = $absensiMapelKelasAjar->filter(fn ($row) => in_array($row->status_harian_masuk, ['izin', 'sakit']) || in_array($row->status_harian_pulang, ['izin', 'sakit']))->count();
            $belumMapel = $absensiMapelKelasAjar->whereNull('absensi_mapel_id')->count();
            if ($izinSakit > 0) {
                buatNotifikasiRoleHarian((int) $user->id, 'guru_siswa_izin_sakit', 'Siswa Izin/Sakit di Kelas Ajar', $izinSakit.' siswa kelas ajar Anda berstatus izin/sakit pada '.$tanggalFilter.'.', ['tanggal' => $tanggalFilter, 'total' => $izinSakit]);
            }
            if ($belumMapel > 0) {
                buatNotifikasiRoleHarian((int) $user->id, 'guru_belum_absen_mapel', 'Belum Absen Mapel', $belumMapel.' siswa belum memiliki absen mapel pada '.$tanggalFilter.'.', ['tanggal' => $tanggalFilter, 'total' => $belumMapel]);
            }
        }

        $rekapJadwalGuru = $semuaJadwalGuru
            ->when($rekapHariFilter, fn ($items) => $items->filter(fn ($item) => strtolower((string) $item->hari) === strtolower((string) $rekapHariFilter)))
            ->when($rekapJpFilter, fn ($items) => $items->where('id', (int) $rekapJpFilter))
            ->when($rekapKelasFilter, fn ($items) => $items->where('kelas_id', (int) $rekapKelasFilter))
            ->when($rekapMapelFilter, fn ($items) => $items->where('mapel_id', (int) $rekapMapelFilter))
            ->when($rekapPeranFilter, fn ($items) => $items->where('role_mengajar', $rekapPeranFilter))
            ->when($rekapTahunAjaranFilter, fn ($items) => $items->filter(fn ($item) => (string) ($item->tahun_ajaran_id ?? '') === (string) $rekapTahunAjaranFilter))
            ->values();

        $rekapMapelOptions = $semuaJadwalGuru
            ->map(fn ($item) => (object) ['id' => $item->mapel_id, 'nama_mapel' => $item->nama_mapel])
            ->unique('id')->sortBy('nama_mapel')->values();

        $isWaliKelas = DB::table(

            'kelas'

        )
            ->where(

                'wali_kelas_id',

                $user->id

            )
            ->exists();

        $hariPiketSekarang = strtolower(now()->locale('id')->translatedFormat('l'));

        $tugasPiketAktif = $dutyResolver->resolve($user, $tanggalHariIni);
        $isPastDutyCutoff = $dutyAssignments->isPastCutoff(now('Asia/Jakarta'));

        $isGuruPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $user->id)
            ->where('hari', $hariPiketSekarang)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->exists();

        $isGuruPiketPenggantiAktifHariIni = $tugasPiketAktif && $tugasPiketAktif->role !== 'utama';

        $punyaAksesGuruPiket = $tugasPiketAktif !== null;
        $infoLiburHariIni = infoLiburHariIni('guru');

        return view(

            'dashboard.guru',

            compact(

                'user',

                'jadwal',
                'statusMengajarHariIni',
                'tugasSaatIni',

                'hari',

                'isWaliKelas',

                'isGuruPiketHariIni',

                'punyaAksesGuruPiket',
                'isGuruPiketPenggantiAktifHariIni',
                'tugasPiketAktif',
                'isPastDutyCutoff',
                'infoLiburHariIni',

                'absensiKelasAjar',

                'absensiMapelKelasAjar',

                'riwayatAbsensiKelasAjar',
                'riwayatAbsensiMapelGuru',
                'semuaJadwalGuru',

                'rekapSiswaGuru',

                'rekapAbsensiMapelGuru',

                'kelasAjar',

                'jurusan',

                'tanggalFilter',

                'hariFilter',

                'bulanFilter',

                'tahunFilter',

                'kelasFilter',

                'jurusanFilter',
                'statusHarianFilter',
                'jadwalFilter',
                'jadwalMapelFilterOptions',
                'ringkasanGuru',
                'tahunAjaran',
                'tahunAjaranId',
                'semesterFilter',
                'rekapJadwalGuru',
                'rekapMapelOptions',
                'rekapHariFilter',
                'rekapJpFilter',
                'rekapKelasFilter',
                'rekapMapelFilter',
                'rekapPeranFilter',
                'rekapTahunAjaranFilter',
                'hariKalenderMengajar'

            ) + [
                'activeGuruPage' => $request->get('page', 'dashboard'),
                'siswaNonaktifKelasAjarCount' => $siswaNonaktifKelasAjarCount,
            ]

        );
    }
    public function jadwal(Request $request)
    {
        return redirect('/dashboard/guru?page=jadwal');
    }

    public function statusMengajar(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'status_mengajar'])));
    }

    public function kalenderMengajar(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'kalender_mengajar'])));
    }

    public function verifikasiAbsensi(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'verifikasi'])));
    }

    public function riwayatAbsensi(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'riwayat'])));
    }

    public function rekapSiswa(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_siswa'])));
    }

    public function rekapAbsensi(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_absensi'])));
    }

    public function rekapJadwal(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_jadwal'])));
    }

    public function piketRekapJadwal(Request $request)
    {
        return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'jadwal'])));
    }
}
