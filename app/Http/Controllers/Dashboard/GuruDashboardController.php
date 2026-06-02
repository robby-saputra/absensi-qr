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
            ->leftJoin(

                'users as gp',

                'gp.id',

                '=',

                'j.guru_pengganti_id'

            )

        /*
====================================
GURU UTAMA + GURU PENGGANTI
====================================
*/
            ->where(function (

                $q

            ) use (

                $user

            ) {

                /*
Guru utama
*/

                $q->where(

                    'j.guru_id',

                    $user->id

                );

                /*
Guru pengganti
*/

                $q->orWhere(function (

                    $x

                ) use (

                    $user

                ) {

                    $x
                        ->where(

                            'j.guru_pengganti_id',

                            $user->id

                        )
                        ->where(

                            'j.status_guru',

                            'digantikan'

                        );

                });

            })
            ->where(

                'j.hari',

                $hari

            )
            ->select(

                'j.*',

                'k.nama_kelas',

                'm.nama_mapel',

                'gp.nama as guru_pengganti',

                'j.keterangan',

                'j.status_guru',

                'j.alasan_tidak_hadir',

                /*
Buat cek
guru login
guru utama
atau pengganti
*/

                DB::raw(

                    "

CASE


WHEN

j.guru_id

=

{$user->id}


THEN

'guru_utama'



WHEN

j.guru_pengganti_id

=

{$user->id}


THEN

'guru_pengganti'



END


as

role_mengajar

"

                )

            )
            ->orderBy(

                'j.jam_mulai'

            )
            ->get();

        $semuaJadwalGuru = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->leftJoin('users as gu', 'gu.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->where(function ($query) use ($user) {
                $query->where('j.guru_id', $user->id)
                    ->orWhere(function ($pengganti) use ($user) {
                        $pengganti->where('j.guru_pengganti_id', $user->id)
                            ->where('j.status_guru', 'digantikan');
                    });
            })
            ->select(
                'j.*',
                'k.nama_kelas',
                'jr.nama_jurusan',
                'm.nama_mapel',
                'gu.nama as guru_utama',
                'gp.nama as guru_pengganti',
                DB::raw("CASE WHEN j.guru_id = {$user->id} THEN 'guru_utama' WHEN j.guru_pengganti_id = {$user->id} THEN 'guru_pengganti' END as role_mengajar")
            )
            ->orderBy('j.hari')
            ->orderBy('j.jam_mulai')
            ->get();

        $kelasAjarIds = $jadwal
            ->pluck('kelas_id')
            ->filter()
            ->unique()
            ->values();

        $absensiKelasAjar = collect();
        $absensiMapelKelasAjar = collect();
        $riwayatAbsensiKelasAjar = collect();
        $rekapSiswaGuru = collect();
        $rekapAbsensiMapelGuru = collect();
        $sesiDigantikanGuru = collect();
        $sesiMenggantiGuru = collect();
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
                        ->whereDate('a.tanggal', request()->get('tanggal', now()->toDateString()));
                })
                ->where('s.role', 'siswa')
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
                ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
                ->join('users as s', function ($join) {
                    $join->on('s.kelas_id', '=', 'j.kelas_id')
                        ->where('s.role', 'siswa');
                })
                ->leftJoin('absensi_mapels as am', function ($join) use ($tanggalFilter) {
                    $join->on('am.jadwal_id', '=', 'j.id')
                        ->on('am.siswa_id', '=', 's.id')
                        ->whereDate('am.tanggal', $tanggalFilter);
                })
                ->leftJoin('absensis as ah', function ($join) use ($tanggalFilter) {
                    $join->on('ah.id_siswa', '=', 's.id')
                        ->whereDate('ah.tanggal', $tanggalFilter);
                })
                ->whereIn('j.id', $jadwal->pluck('id'))
                ->whereIn('j.kelas_id', $filteredKelasIds)
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
                    'j.guru_pengganti_id',
                    'gp.nama as guru_pengganti',
                    's.id as siswa_id',
                    's.nama',
                    's.nis',
                    'k.nama_kelas',
                    'jr.nama_jurusan',
                    'm.nama_mapel',
                    DB::raw("CASE WHEN j.status_guru = 'digantikan' AND j.guru_pengganti_id = {$user->id} THEN 1 WHEN (j.status_guru IS NULL OR j.status_guru = 'normal') AND j.guru_id = {$user->id} THEN 1 ELSE 0 END as boleh_kelola_mapel")
                )
                ->orderBy('k.nama_kelas')
                ->orderBy('m.nama_mapel')
                ->orderBy('s.nama')
                ->get();

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
                ->whereIn('s.kelas_id', $filteredKelasIds)
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
            $sesiDigantikanGuru = DB::table('jadwal_pelajarans as j')
                ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
                ->leftJoin('absensi_mapels as am', function ($join) use ($tanggalFilter) {
                    $join->on('am.jadwal_id', '=', 'j.id')
                        ->whereDate('am.tanggal', $tanggalFilter);
                })
                ->where('j.guru_id', $user->id)
                ->where('j.status_guru', 'digantikan')
                ->select(
                    'j.id',
                    'j.kelas_id',
                    'j.hari',
                    'j.jam_mulai',
                    'j.jam_selesai',
                    'j.alasan_tidak_hadir',
                    'k.nama_kelas',
                    'm.nama_mapel',
                    'gp.nama as guru_pengganti',
                    DB::raw('COUNT(am.id) as total_absen_mapel')
                )
                ->groupBy('j.id', 'j.kelas_id', 'j.hari', 'j.jam_mulai', 'j.jam_selesai', 'j.alasan_tidak_hadir', 'k.nama_kelas', 'm.nama_mapel', 'gp.nama')
                ->orderBy('j.hari')
                ->orderBy('j.jam_mulai')
                ->get();

            $sesiMenggantiGuru = DB::table('jadwal_pelajarans as j')
                ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->join('users as gu', 'gu.id', '=', 'j.guru_id')
                ->leftJoin('absensi_mapels as am', function ($join) use ($tanggalFilter) {
                    $join->on('am.jadwal_id', '=', 'j.id')
                        ->whereDate('am.tanggal', $tanggalFilter);
                })
                ->where('j.guru_pengganti_id', $user->id)
                ->where('j.status_guru', 'digantikan')
                ->select(
                    'j.id',
                    'j.kelas_id',
                    'j.hari',
                    'j.jam_mulai',
                    'j.jam_selesai',
                    'k.nama_kelas',
                    'm.nama_mapel',
                    'gu.nama as guru_utama',
                    DB::raw('COUNT(am.id) as total_absen_mapel')
                )
                ->groupBy('j.id', 'j.kelas_id', 'j.hari', 'j.jam_mulai', 'j.jam_selesai', 'k.nama_kelas', 'm.nama_mapel', 'gu.nama')
                ->orderBy('j.hari')
                ->orderBy('j.jam_mulai')
                ->get();

            $rekapSiswaGuru = DB::table('users as s')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
                ->where('s.role', 'siswa')
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
                ->where(function ($query) use ($user) {
                    $query->where('j.guru_id', $user->id)
                        ->orWhere(function ($pengganti) use ($user) {
                            $pengganti->where('j.guru_pengganti_id', $user->id)
                                ->where('j.status_guru', 'digantikan');
                        });
                })
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
            ->exists();

        $isGuruPiketPenggantiHariIni = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereIn('status', ['Izin', 'Sakit'])
            ->where(function ($query) use ($user) {
                $query->where('guru_pengganti_id', $user->id)
                    ->orWhere('guru_pengganti2_id', $user->id);
            })
            ->exists();

        $punyaAksesGuruPiket = DB::table('guru_pikets')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($user) {
                $query->where('guru_id', $user->id)
                    ->orWhere('guru_pengganti_id', $user->id)
                    ->orWhere('guru_pengganti2_id', $user->id);
            })
            ->exists();
        $infoLiburHariIni = infoLiburHariIni('guru');

        return view(

            'dashboard.guru',

            compact(

                'user',

                'jadwal',

                'hari',

                'isWaliKelas',

                'isGuruPiketHariIni',

                'isGuruPiketPenggantiHariIni',

                'punyaAksesGuruPiket',
                'infoLiburHariIni',

                'absensiKelasAjar',

                'absensiMapelKelasAjar',

                'riwayatAbsensiKelasAjar',
                'semuaJadwalGuru',

                'rekapSiswaGuru',

                'rekapAbsensiMapelGuru',

                'sesiDigantikanGuru',

                'sesiMenggantiGuru',

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

            ) + ['activeGuruPage' => $request->get('page', 'dashboard')]

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

    public function sesiDigantikan(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'sesi_digantikan'])));
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
