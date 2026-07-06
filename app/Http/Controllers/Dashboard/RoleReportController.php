<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleReportController extends Controller
{
    private function applyJadwalGuruFilters($query, Request $request, int $userId)
    {
        return $query
            ->when($request->get('rekap_hari'), fn ($q, $value) => $q->whereRaw('LOWER(j.hari) = ?', [strtolower($value)]))
            ->when($request->get('rekap_jadwal_id'), fn ($q, $value) => $q->where('j.id', $value))
            ->when($request->get('rekap_kelas_id'), fn ($q, $value) => $q->where('j.kelas_id', $value))
            ->when($request->get('rekap_mapel_id'), fn ($q, $value) => $q->where('j.mapel_id', $value))
            ->when($request->get('rekap_tahun_ajaran_id'), fn ($q, $value) => $q->where('j.tahun_ajaran_id', $value))
            ->when($request->get('rekap_peran') === 'guru_utama', fn ($q) => $q->where('j.guru_id', $userId))
            ->when($request->get('rekap_peran') === 'guru_pengganti', fn ($q) => $q->where('j.guru_pengganti_id', $userId));
    }

    private function applyAbsensiMapelFilters($query, Request $request)
    {
        $status = $request->get('status_harian');

        return $query
            ->when($request->get('jadwal_id'), fn ($q, $value) => $q->where('j.id', $value))
            ->when($request->get('kelas_id'), fn ($q, $value) => $q->where('j.kelas_id', $value))
            ->when($request->get('jurusan_id'), fn ($q, $value) => $q->where('k.jurusan_id', $value))
            ->when($request->get('tahun_ajaran_id'), fn ($q, $value) => $q->where(function ($where) use ($value) {
                $where->where('a.tahun_ajaran_id', $value)->orWhere('ah.tahun_ajaran_id', $value)->orWhereNull('a.tahun_ajaran_id');
            }))
            ->when(in_array($status, ['izin', 'sakit']), fn ($q) => $q->where(function ($where) use ($status) {
                $where->where('ah.status_masuk', $status)->orWhere('ah.status_pulang', $status);
            }))
            ->when($status === 'alfa', fn ($q) => $q->where(function ($where) {
                $where->whereNull('ah.id')->orWhereIn('ah.status_masuk', ['alfa', 'alpa'])->orWhereIn('ah.status_pulang', ['alfa', 'alpa']);
            }))
            ->when($status === 'hadir', fn ($q) => $q->whereNotNull('ah.jam_masuk')->whereNotIn(DB::raw('COALESCE(ah.status_masuk, "")'), ['izin','sakit','alfa','alpa']));
    }

    private function queryJadwalGuru($userId)
    {
        return DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->whereNull('j.deleted_at')
            ->whereNotNull('j.jam_ke_mulai')
            ->whereNotNull('j.jumlah_jp')
            ->where(function ($query) use ($userId) {
                $query->where('j.guru_id', $userId)
                    ->orWhere('j.guru_pengganti_id', $userId);
            });
    }

    private function queryRekapAbsensiMapelGuru($userId)
    {
        return DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as s', function ($join) {
                $join->on('s.kelas_id', '=', 'j.kelas_id')
                    ->where('s.role', 'siswa')
                    ->where('s.aktif', 1)
                    ->whereNull('s.deleted_at');
            })
            ->leftJoin('absensis as ah', function ($join) {
                $join->on('ah.id_siswa', '=', 's.id')
                    ->whereNull('ah.deleted_at');
            })
            ->leftJoin('absensi_mapels as a', function ($join) {
                $join->on('a.jadwal_id', '=', 'j.id')
                    ->on('a.siswa_id', '=', 's.id')
                    ->whereColumn('a.tanggal', 'ah.tanggal')
                    ->whereNull('a.deleted_at');
            })
            ->whereNull('j.deleted_at')
            ->whereNotNull('j.jam_ke_mulai')
            ->whereNotNull('j.jumlah_jp')
            ->where(function ($query) use ($userId) {
                $query->where('j.guru_id', $userId)
                    ->orWhere('j.guru_pengganti_id', $userId);
            });
    }

    private function statusHarianLabel($row): string
    {
        if (in_array($row->status_harian_masuk, ['izin', 'sakit', 'alfa', 'alpa'])) {
            return $row->status_harian_masuk;
        }

        if (in_array($row->status_harian_pulang, ['izin', 'sakit', 'alfa', 'alpa'])) {
            return $row->status_harian_pulang;
        }

        return $row->status_harian_masuk ?: ($row->jam_harian_masuk ? 'hadir' : 'alfa');
    }

    public function guruPdf(Request $request, $type)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());
        $headers = [];
        $rows = collect();
        $title = 'Rekap Guru Mapel';

        if ($type === 'jadwal') {
            $title = 'Rekap Jadwal Guru Mapel';
            $headers = ['Hari', 'Kelas', 'Jurusan', 'Mapel', 'Jam', 'Peran', 'Status'];
            $rows = $this->applyJadwalGuruFilters($this->queryJadwalGuru($user->id), $request, (int) $user->id)
                ->select('j.*', 'k.nama_kelas', 'jr.nama_jurusan', 'm.nama_mapel')
                ->orderBy('j.hari')
                ->orderBy('j.jam_mulai')
                ->get()
                ->map(fn ($r) => [
                    $r->hari,
                    $r->nama_kelas,
                    $r->nama_jurusan ?: '-',
                    $r->nama_mapel,
                    labelJadwalJp($r),
                    (int) ($r->guru_pengganti_id ?? 0) === (int) $user->id ? 'Guru Pengganti' : 'Guru Utama',
                    $r->status_guru ?: 'normal',
                ]);
        } else {
            $title = $type === 'siswa' ? 'Rekap Siswa Guru Mapel' : 'Rekap Absensi Mapel Guru';
            $headers = $type === 'siswa'
                ? ['Nama', 'NIS', 'Kelas']
                : ['Tanggal', 'Nama', 'Kelas', 'Mapel', 'Jam Pelajaran', 'Status Harian', 'Jam Scan', 'Status Mapel', 'Catatan'];
            if ($type === 'siswa') {
                $kelasIds = DB::table('jadwal_pelajarans')->where('guru_id', $user->id)->pluck('kelas_id')->unique();
                $rows = DB::table('users as s')->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')->where('s.role', 'siswa')->whereIn('s.kelas_id', $kelasIds)->select('s.nama', 's.nis', 'k.nama_kelas')->orderBy('k.nama_kelas')->orderBy('s.nama')->get()->map(fn ($r) => [$r->nama, $r->nis ?: '-', $r->nama_kelas ?: '-']);
            } else {
                $rows = $this->applyAbsensiMapelFilters($this->queryRekapAbsensiMapelGuru($user->id), $request)
                    ->where(function ($query) use ($tanggal) {
                        $query->whereDate('a.tanggal', $tanggal)
                            ->orWhereDate('ah.tanggal', $tanggal);
                    })
                    ->select(
                        DB::raw('COALESCE(a.tanggal, ah.tanggal) as tanggal_rekap'),
                        's.nama',
                        'k.nama_kelas',
                        'm.nama_mapel',
                        'j.jam_mulai',
                        'j.jam_selesai',
                        'j.jam_ke_mulai',
                        'j.jumlah_jp',
                        'ah.jam_masuk as jam_harian_masuk',
                        'ah.status_masuk as status_harian_masuk',
                        'ah.status_pulang as status_harian_pulang',
                        'a.jam_scan',
                        'a.status',
                        'a.catatan_guru'
                    )
                    ->orderBy('k.nama_kelas')
                    ->orderBy('s.nama')
                    ->get()
                    ->map(fn ($r) => [
                        $r->tanggal_rekap,
                        $r->nama,
                        $r->nama_kelas ?: '-',
                        $r->nama_mapel,
                        labelJadwalJp($r),
                        trim(($r->jam_harian_masuk ?: '-').' '.$this->statusHarianLabel($r)),
                        $r->jam_scan ?: '-',
                        $r->status ?: 'belum absen mapel',
                        $r->catatan_guru ?? '-',
                    ]);
            }
        }

        return view('dashboard.pdf.official_table', ['title' => $title, 'meta' => 'Dicetak oleh '.$user->nama.' pada '.now()->format('d-m-Y H:i'), 'headers' => $headers, 'rows' => $rows]);
    }

    public function piketPdf(Request $request, $type)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());
        $headers = $type === 'jadwal'
            ? ['Guru', 'Hari', 'Jam', 'Status']
            : ['Tanggal', 'Nama', 'NIS', 'Kelas', 'Masuk', 'Pulang', 'Catatan'];
        $title = $type === 'jadwal' ? 'Rekap Jadwal Guru Piket' : 'Rekap Absensi Harian Piket';
        $rows = $type === 'jadwal'
            ? DB::table('guru_pikets as gp')->join('users as g', 'g.id', '=', 'gp.guru_id')->leftJoin('guru_piket_statuses as gps', fn ($join) => $join->on('gps.guru_piket_id', '=', 'gp.id')->on('gps.guru_id', '=', 'gp.guru_id')->whereDate('gps.tanggal', $tanggal)->whereNull('gps.deleted_at'))->whereNull('gp.deleted_at')->select('g.nama as guru', 'gp.*', DB::raw("COALESCE(gps.status, 'belum_konfirmasi') as status_harian"))->orderBy('gp.hari')->orderBy('gp.jam_mulai')->get()->map(fn ($r) => [$r->guru, $r->hari, $r->jam_mulai.' - '.$r->jam_selesai, $r->status_harian])
            : DB::table('absensis as a')->join('users as s', 's.id', '=', 'a.id_siswa')->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')->whereNull('a.deleted_at')->whereDate('a.tanggal', $tanggal)->select('a.*', 's.nama', 's.nis', 'k.nama_kelas')->orderBy('k.nama_kelas')->orderBy('s.nama')->get()->map(fn ($r) => [$r->tanggal, $r->nama, $r->nis ?: '-', $r->nama_kelas ?: '-', trim(($r->jam_masuk ?: '-').' '.($r->status_masuk ?: '')), trim(($r->jam_pulang ?: '-').' '.($r->status_pulang ?: '')), $r->catatan_piket ?? '-']);

        return view('dashboard.pdf.official_table', ['title' => $title, 'meta' => 'Dicetak oleh '.$user->nama.' pada '.now()->format('d-m-Y H:i'), 'headers' => $headers, 'rows' => $rows]);
    }

    public function waliPdf(Request $request, $type)
    {
        $user = session('user');
        $wali = DB::table('kelas')->where('wali_kelas_id', $user->id)->whereNull('deleted_at')->first();
        abort_if(! $wali, 403);
        $headers = $type === 'siswa'
            ? ['Nama', 'Username', 'NIS', 'Nama Orang Tua', 'No Orang Tua']
            : ['Tanggal', 'Nama', 'Masuk', 'Pulang', 'Status'];
        $title = $type === 'siswa' ? 'Daftar Siswa Wali Kelas' : 'Rekap Absensi Wali Kelas';
        $rows = $type === 'siswa'
            ? DB::table('users')->where('role', 'siswa')->where('kelas_id', $wali->id)->orderBy('nama')->get()->map(fn ($r) => [$r->nama, $r->username, $r->nis ?: '-', $r->nama_ortu ?: '-', $r->no_ortu ?: '-'])
            : DB::table('absensis as a')->join('users as s', 's.id', '=', 'a.id_siswa')->where('s.kelas_id', $wali->id)->whereNull('a.deleted_at')->when($request->filled('tanggal'), fn ($q) => $q->whereDate('a.tanggal', $request->tanggal))->select('a.*', 's.nama')->orderByDesc('a.tanggal')->limit(300)->get()->map(fn ($r) => [$r->tanggal, $r->nama, ($r->jam_masuk ?: '-').' '.($r->status_masuk ?: ''), ($r->jam_pulang ?: '-').' '.($r->status_pulang ?: ''), $r->status_masuk ?: '-']);

        return view('dashboard.pdf.official_table', ['title' => $title, 'meta' => $wali->nama_kelas.' - dicetak oleh '.$user->nama, 'headers' => $headers, 'rows' => $rows]);
    }

    public function waliSurat(Request $request, $siswaId)
    {
        $user = session('user');
        $wali = DB::table('kelas')->where('wali_kelas_id', $user->id)->whereNull('deleted_at')->first();
        abort_if(! $wali, 403);
        $siswa = DB::table('users')->where('role', 'siswa')->where('kelas_id', $wali->id)->where('id', $siswaId)->first();
        abort_if(! $siswa, 404);
        $catatan = collect();
        $rekap = DB::table('absensis')
            ->where('id_siswa', $siswaId)
            ->whereNull('deleted_at')
            ->whereDate('tanggal', '>=', now()->subDays(30)->toDateString())
            ->selectRaw("
            SUM(CASE WHEN status_masuk = 'telat' THEN 1 ELSE 0 END) as telat,
            SUM(CASE WHEN status_masuk IN ('alfa','alpa') OR status_pulang IN ('alfa','alpa') THEN 1 ELSE 0 END) as alfa,
            SUM(CASE WHEN status_masuk = 'izin' OR status_pulang = 'izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN status_masuk = 'sakit' OR status_pulang = 'sakit' THEN 1 ELSE 0 END) as sakit
        ")
            ->first();

        return view('dashboard.wali_surat', compact('user', 'wali', 'siswa', 'catatan', 'rekap'));
    }

    public function guruLaporanBulanan(Request $request)
    {
        $user = session('user');
        [$mulai, $selesai, $bulan] = periodeBulan($request->get('bulan'));
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        if ($request->get('type') === 'jadwal') {
            $headers = ['Hari', 'Kelas', 'Jurusan', 'Mapel', 'Jam', 'Peran', 'Status'];
            $rows = $this->applyJadwalGuruFilters($this->queryJadwalGuru($user->id), $request, (int) $user->id)
                ->select('j.*', 'k.nama_kelas', 'jr.nama_jurusan', 'm.nama_mapel')
                ->orderBy('j.hari')
                ->orderBy('j.jam_mulai')
                ->get()
                ->map(fn ($r) => [
                    $r->hari,
                    $r->nama_kelas,
                    $r->nama_jurusan ?: '-',
                    $r->nama_mapel,
                    labelJadwalJp($r),
                    (int) ($r->guru_pengganti_id ?? 0) === (int) $user->id ? 'Guru Pengganti' : 'Guru Utama',
                    $r->status_guru ?: 'normal',
                ]);

            return view('dashboard.pdf.official_table', ['title' => 'Laporan Bulanan Jadwal Guru Mapel', 'meta' => $user->nama.' | Periode '.$bulan, 'headers' => $headers, 'rows' => $rows]);
        }

        $headers = ['Tanggal', 'Siswa', 'Kelas', 'Mapel', 'Jam Pelajaran', 'Status Harian', 'Jam Scan', 'Status Mapel', 'Catatan'];
        $rows = $this->applyAbsensiMapelFilters($this->queryRekapAbsensiMapelGuru($user->id), $request)
            ->where(function ($query) use ($mulai, $selesai) {
                $query->whereBetween('a.tanggal', [$mulai, $selesai])
                    ->orWhereBetween('ah.tanggal', [$mulai, $selesai]);
            })
            ->when($tahunAjaranId, fn ($q) => $q->where(function ($where) use ($tahunAjaranId) {
                $where->where('a.tahun_ajaran_id', $tahunAjaranId)
                    ->orWhere('ah.tahun_ajaran_id', $tahunAjaranId)
                    ->orWhereNull('a.tahun_ajaran_id');
            }))
            ->select(
                DB::raw('COALESCE(a.tanggal, ah.tanggal) as tanggal_rekap'),
                's.nama',
                'k.nama_kelas',
                'm.nama_mapel',
                'j.jam_mulai',
                'j.jam_selesai',
                'j.jam_ke_mulai',
                'j.jumlah_jp',
                'ah.jam_masuk as jam_harian_masuk',
                'ah.status_masuk as status_harian_masuk',
                'ah.status_pulang as status_harian_pulang',
                'a.jam_scan',
                'a.status',
                'a.catatan_guru'
            )
            ->orderBy('tanggal_rekap')->orderBy('k.nama_kelas')->orderBy('s.nama')
            ->get()
            ->map(fn ($r) => [
                $r->tanggal_rekap,
                $r->nama,
                $r->nama_kelas ?: '-',
                $r->nama_mapel ?: '-',
                labelJadwalJp($r),
                trim(($r->jam_harian_masuk ?: '-').' '.$this->statusHarianLabel($r)),
                $r->jam_scan ?: '-',
                $r->status ?: 'belum absen mapel',
                $r->catatan_guru ?? '-',
            ]);

        return view('dashboard.pdf.official_table', ['title' => 'Laporan Bulanan Guru Mapel', 'meta' => $user->nama.' | Periode '.$bulan, 'headers' => $headers, 'rows' => $rows]);
    }

    public function piketLaporanBulanan(Request $request)
    {
        $user = session('user');
        [$mulai, $selesai, $bulan] = periodeBulan($request->get('bulan'));
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $headers = ['Tanggal', 'Siswa', 'NIS', 'Kelas', 'Masuk', 'Pulang', 'Catatan'];
        $rows = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->whereNull('a.deleted_at')
            ->whereBetween('a.tanggal', [$mulai, $selesai])
            ->when($tahunAjaranId, fn ($q) => $q->where('a.tahun_ajaran_id', $tahunAjaranId))
            ->select('a.*', 's.nama', 's.nis', 'k.nama_kelas')
            ->orderBy('a.tanggal')->orderBy('k.nama_kelas')->orderBy('s.nama')
            ->get()
            ->map(fn ($r) => [$r->tanggal, $r->nama, $r->nis ?: '-', $r->nama_kelas ?: '-', trim(($r->jam_masuk ?: '-').' '.($r->status_masuk ?: '')), trim(($r->jam_pulang ?: '-').' '.($r->status_pulang ?: '')), $r->catatan_piket ?? '-']);

        return view('dashboard.pdf.official_table', ['title' => 'Laporan Bulanan Guru Piket', 'meta' => $user->nama.' | Periode '.$bulan, 'headers' => $headers, 'rows' => $rows]);
    }

    public function waliLaporanBulanan(Request $request)
    {
        $user = session('user');
        $wali = DB::table('kelas')->where('wali_kelas_id', $user->id)->whereNull('deleted_at')->first();
        abort_if(! $wali, 403);
        [$mulai, $selesai, $bulan] = periodeBulan($request->get('bulan'));
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $headers = ['Tanggal', 'Siswa', 'Masuk', 'Pulang', 'Status'];
        $rows = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->where('s.kelas_id', $wali->id)
            ->whereNull('a.deleted_at')
            ->whereBetween('a.tanggal', [$mulai, $selesai])
            ->when($tahunAjaranId, fn ($q) => $q->where('a.tahun_ajaran_id', $tahunAjaranId))
            ->select('a.*', 's.nama')
            ->orderBy('a.tanggal')->orderBy('s.nama')
            ->get()
            ->map(fn ($r) => [$r->tanggal, $r->nama, ($r->jam_masuk ?: '-').' '.($r->status_masuk ?: ''), ($r->jam_pulang ?: '-').' '.($r->status_pulang ?: ''), $r->status_masuk ?: '-']);

        return view('dashboard.pdf.official_table', ['title' => 'Laporan Bulanan Wali Kelas', 'meta' => $wali->nama_kelas.' | '.$user->nama.' | Periode '.$bulan, 'headers' => $headers, 'rows' => $rows]);
    }

}
