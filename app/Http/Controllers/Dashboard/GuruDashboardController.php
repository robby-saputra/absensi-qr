<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuruDashboardController extends Controller
{

    public function index(Request $request)
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
            ->where('j.guru_id', $user->id)
            ->where(

                'j.hari',

                $hari

            )
            ->whereNull('j.deleted_at')
            ->select(

                'j.*',

                'k.nama_kelas',

                'm.nama_mapel',

                'j.keterangan',

                'j.status_guru',

                'j.alasan_tidak_hadir',
                DB::raw("'guru_utama' as role_mengajar")

            )
            ->orderBy(

                'j.jam_mulai'

            )
            ->get();

        $jamSekarang = now()->format('H:i:s');

        $tugasSaatIni = $jadwal
            ->filter(fn ($item) => $item->jam_mulai <= $jamSekarang && $item->jam_selesai >= $jamSekarang)
            ->map(function ($item) {
                return (object) [
                    'jenis' => 'Guru Mapel',
                    'detail' => trim(($item->nama_mapel ?? '-').' - '.($item->nama_kelas ?? '-')),
                    'jam_mulai' => $item->jam_mulai,
                    'jam_selesai' => $item->jam_selesai,
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

        $semuaJadwalGuru = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->leftJoin('users as gu', 'gu.id', '=', 'j.guru_id')
            ->where('j.guru_id', $user->id)
            ->whereNull('j.deleted_at')
            ->select(
                'j.*',
                'k.nama_kelas',
                'jr.nama_jurusan',
                'm.nama_mapel',
                'gu.nama as guru_utama',
                DB::raw("'guru_utama' as role_mengajar")
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
        $rekapSiswaGuru = collect();
        $rekapAbsensiMapelGuru = collect();
        $siswaNonaktifKelasAjarCount = 0;
        $tanggalFilter = $request->get('tanggal', now()->toDateString());
        $liburTanggalFilter = hariLiburSekolah($tanggalFilter);
        $hariFilter = $request->get('hari');
        $bulanFilter = $request->get('bulan');
        $tahunFilter = $request->get('tahun');
        $kelasFilter = $request->get('kelas_id');
        $jurusanFilter = $request->get('jurusan_id');
        $statusHarianFilter = $request->get('status_harian');
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $semesterFilter = $request->get('semester') ?: optional($tahunAjaran->firstWhere('id', $tahunAjaranId))->semester;
        $kelasAjar = collect();
        $jurusan = DB::table('jurusan')->orderBy('nama_jurusan')->get();
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
                ->whereIn('j.id', $jadwalVerifikasiGuruIds)
                ->whereIn('j.kelas_id', $filteredKelasIds)
                ->whereNull('j.deleted_at')
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
                    'j.status_guru',
                    'j.jam_mulai',
                    'j.jam_selesai',
                    'j.guru_id',
                    's.id as siswa_id',
                    's.nama',
                    's.nis',
                    'k.nama_kelas',
                    'jr.nama_jurusan',
                    'm.nama_mapel',
                    DB::raw("'guru_utama' as role_mengajar"),
                    DB::raw('1 as boleh_kelola_mapel')
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
                    return 'siswa-'.$row->siswa_id.'-tanggal-'.($row->tanggal ?: request()->get('tanggal', now()->toDateString()));
                })
                ->sortBy([
                    fn ($row) => empty($row->absensi_mapel_id) ? 1 : 0,
                    fn ($row) => empty($row->jam_scan) ? PHP_INT_MAX : -strtotime((string) $row->jam_scan),
                    fn ($row) => $row->nama_kelas ?? '',
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

            $absensiMapelKelasAjar = $absensiMapelKelasAjar->map(function ($row) use ($tanggalFilter) {
                $row->sesi_terkunci = absensiTerkunci('mapel', $tanggalFilter, (int) $row->jadwal_id, null) ? true : false;

                return $row;
            });

            $ringkasanGuru['izin'] = $absensiMapelKelasAjar->filter(fn ($row) => in_array($row->status_harian_masuk, ['izin']) || in_array($row->status_harian_pulang, ['izin']))->count();
            $ringkasanGuru['sakit'] = $absensiMapelKelasAjar->filter(fn ($row) => in_array($row->status_harian_masuk, ['sakit']) || in_array($row->status_harian_pulang, ['sakit']))->count();
            $ringkasanGuru['alfa'] = $absensiMapelKelasAjar->filter(fn ($row) => in_array($row->status_harian_masuk, ['alfa', 'alpa']) || in_array($row->status_harian_pulang, ['alfa', 'alpa']) || (! $row->jam_harian_masuk && ! in_array($row->status_harian_masuk, ['libur'])))->count();
            $ringkasanGuru['telat'] = $absensiMapelKelasAjar->filter(fn ($row) => $row->status_harian_masuk === 'telat')->count();
            $ringkasanGuru['hadir'] = $absensiMapelKelasAjar->filter(fn ($row) => $row->jam_harian_masuk && ! in_array($row->status_harian_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count();
            $ringkasanGuru['mapel_terisi'] = $absensiMapelKelasAjar->whereNotNull('absensi_mapel_id')->count();
            $ringkasanGuru['mapel_belum'] = max(0, $absensiMapelKelasAjar->count() - $ringkasanGuru['mapel_terisi']);

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

            $rekapAbsensiMapelGuru = DB::table('absensi_mapels as a')
                ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
                ->join('users as s', 's.id', '=', 'a.siswa_id')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->whereNull('a.deleted_at')
                ->where('s.aktif', 1)
                ->whereNull('s.deleted_at')
                ->where('j.guru_id', $user->id)
                ->whereNull('j.deleted_at')
                ->select('a.*', 's.nama as nama_siswa', 'k.nama_kelas', 'm.nama_mapel', 'j.hari', 'j.jam_mulai', 'j.jam_selesai')
                ->latest('a.tanggal')
                ->orderBy('k.nama_kelas')
                ->orderBy('s.nama')
                ->limit(150)
                ->get();
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

        $isWaliKelas = DB::table(

            'kelas'

        )
            ->where(

                'wali_kelas_id',

                $user->id

            )
            ->exists();

        $isGuruPiketHariIni = DB::table('guru_pikets')
            ->where('guru_id', $user->id)
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->exists();

        $punyaAksesGuruPiket = DB::table('guru_pikets')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
            ->exists();
        $infoLiburHariIni = infoLiburHariIni('guru');

        return view(

            'dashboard.guru',

            compact(

                'user',

                'jadwal',
                'tugasSaatIni',

                'hari',

                'isWaliKelas',

                'isGuruPiketHariIni',

                'punyaAksesGuruPiket',
                'infoLiburHariIni',

                'absensiKelasAjar',

                'absensiMapelKelasAjar',

                'riwayatAbsensiKelasAjar',
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
                'ringkasanGuru',
                'tahunAjaran',
                'tahunAjaranId',
                'semesterFilter'

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
