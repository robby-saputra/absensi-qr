<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPdfController extends Controller
{
    public function absensiHarian(Request $request)
    {
        $filters = [
            'mode' => $request->get('mode', 'tanggal'),
            'tanggal' => $request->get('tanggal', now()->toDateString()),
            'bulan' => $request->get('bulan', now()->format('Y-m')),
            'tahun_ajaran_id' => $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId(),
        ];
        $query = DB::table('absensis as a')->join('users as s', 's.id', '=', 'a.id_siswa')->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')->whereNull('a.deleted_at')->select('a.*', 's.nama', 's.nis', 'k.nama_kelas')->orderBy('k.nama_kelas')->orderBy('s.nama');
        if ($filters['tahun_ajaran_id']) {
            $query->where('a.tahun_ajaran_id', $filters['tahun_ajaran_id']);
        }
        if ($filters['mode'] === 'bulan') {
            $query->whereYear('a.tanggal', substr($filters['bulan'], 0, 4))->whereMonth('a.tanggal', substr($filters['bulan'], 5, 2));
        } else {
            $query->whereDate('a.tanggal', $filters['tanggal']);
        }
        $data = $query->get();
        $title = 'Rekap Absensi Harian';

        return view('dashboard.pdf.absensi_harian', compact('data', 'filters', 'title'));
    }

    public function absensiMapel(Request $request)
    {
        $tanggal = $request->get('tanggal');
        $kelasId = $request->get('kelas_id');
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $query = DB::table('absensi_mapels as a')
            ->join('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->whereNull('a.deleted_at')
            ->select('a.tanggal', 's.nama as siswa', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as guru_utama', 'gp.nama as guru_pengganti', 'j.jam_mulai', 'j.jam_selesai', 'a.jam_scan', 'a.status');
        if ($tanggal) {
            $query->whereDate('a.tanggal', $tanggal);
        }
        if ($kelasId) {
            $query->where('s.kelas_id', $kelasId);
        }
        if ($tahunAjaranId) {
            $query->where('a.tahun_ajaran_id', $tahunAjaranId);
        }
        $headers = ['Tanggal', 'Siswa', 'Kelas', 'Mapel', 'Guru Utama', 'Guru Pengganti', 'Jam', 'Scan', 'Status'];
        $rows = $query->latest('a.tanggal')->orderBy('k.nama_kelas')->orderBy('s.nama')->get()->map(fn ($r) => [$r->tanggal, $r->siswa, $r->nama_kelas, $r->nama_mapel, $r->guru_utama, $r->guru_pengganti ?: '-', $r->jam_mulai.' - '.$r->jam_selesai, $r->jam_scan ?: '-', $r->status]);

        return view('dashboard.pdf.official_table', ['title' => 'Rekap Absensi Mapel', 'meta' => 'Tanggal: '.($tanggal ?: 'Semua').' | Tahun ajaran ID: '.($tahunAjaranId ?: 'Semua'), 'headers' => $headers, 'rows' => $rows]);
    }

    public function guruPiket(Request $request)
    {
        $hari = $request->get('hari');
        $status = $request->get('status');
        $query = DB::table('guru_pikets as gp')
            ->join('users as g', 'g.id', '=', 'gp.guru_id')
            ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
            ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
            ->whereNull('gp.deleted_at')
            ->select('g.nama as guru_utama', 'g1.nama as guru_pengganti', 'g2.nama as guru_pengganti2', 'gp.hari', 'gp.jam_mulai', 'gp.jam_selesai', 'gp.status', 'gp.aktif');
        if ($hari) {
            $query->where('gp.hari', strtolower($hari));
        }
        if ($status) {
            $query->where('gp.status', $status);
        }
        $headers = ['Guru Piket', 'Pengganti 1', 'Pengganti 2', 'Hari', 'Jam', 'Status', 'Aktif'];
        $rows = $query->orderBy('gp.hari')->orderBy('gp.jam_mulai')->get()->map(fn ($r) => [$r->guru_utama, $r->guru_pengganti ?: '-', $r->guru_pengganti2 ?: '-', ucfirst($r->hari), $r->jam_mulai.' - '.$r->jam_selesai, $r->status, $r->aktif ? 'Ya' : 'Tidak']);

        return view('dashboard.pdf.official_table', ['title' => 'Rekap Guru Piket', 'meta' => 'Hari: '.($hari ?: 'Semua').' | Status: '.($status ?: 'Semua'), 'headers' => $headers, 'rows' => $rows]);
    }

    public function jadwalDigantikan(Request $request)
    {
        $hari = $request->get('hari');
        $alasan = $request->get('alasan');
        $query = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->where('j.status_guru', 'digantikan')
            ->whereNull('j.deleted_at')
            ->select('j.hari', 'j.jam_mulai', 'j.jam_selesai', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as guru_utama', 'gp.nama as guru_pengganti', 'j.alasan_tidak_hadir');
        if ($hari) {
            $query->where('j.hari', $hari);
        }
        if ($alasan) {
            $query->where('j.alasan_tidak_hadir', $alasan);
        }
        $headers = ['Hari', 'Jam', 'Kelas', 'Mapel', 'Guru Utama', 'Pengganti', 'Alasan'];
        $rows = $query->orderBy('j.hari')->orderBy('j.jam_mulai')->get()->map(fn ($r) => [$r->hari, $r->jam_mulai.' - '.$r->jam_selesai, $r->nama_kelas, $r->nama_mapel, $r->guru_utama, $r->guru_pengganti ?: '-', ucfirst($r->alasan_tidak_hadir ?: '-')]);

        return view('dashboard.pdf.official_table', ['title' => 'Rekap Jadwal Digantikan', 'meta' => 'Hari: '.($hari ?: 'Semua').' | Alasan: '.($alasan ?: 'Semua'), 'headers' => $headers, 'rows' => $rows]);
    }

    public function jadwalGuruMapel(Request $request)
    {
        $guruId = $request->get('guru_id');
        $hari = $request->get('hari');
        $query = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->whereNull('j.deleted_at')
            ->select('g.nama as guru_utama', 'j.hari', 'j.jam_mulai', 'j.jam_selesai', 'k.nama_kelas', 'm.nama_mapel', 'gp.nama as guru_pengganti', 'j.status_guru');
        if ($guruId) {
            $query->where('j.guru_id', $guruId);
        }
        if ($hari) {
            $query->where('j.hari', $hari);
        }
        $headers = ['Guru', 'Hari', 'Jam', 'Kelas', 'Mapel', 'Pengganti', 'Status'];
        $rows = $query->orderBy('g.nama')->orderBy('j.hari')->orderBy('j.jam_mulai')->get()->map(fn ($r) => [$r->guru_utama, $r->hari, $r->jam_mulai.' - '.$r->jam_selesai, $r->nama_kelas, $r->nama_mapel, $r->guru_pengganti ?: '-', $r->status_guru ?: 'belum dipilih']);

        return view('dashboard.pdf.official_table', ['title' => 'Rekap Jadwal Guru Mapel', 'meta' => 'Guru ID: '.($guruId ?: 'Semua').' | Hari: '.($hari ?: 'Semua'), 'headers' => $headers, 'rows' => $rows]);
    }

    public function waliKelas()
    {
        $data = DB::table('kelas as k')
            ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')
            ->leftJoin('users as s', function ($join) {
                $join->on('s.kelas_id', '=', 'k.id')->where('s.role', 'siswa')->whereNull('s.deleted_at');
            })
            ->whereNull('k.deleted_at')
            ->select('k.nama_kelas', 'w.nama as wali_kelas', DB::raw('COUNT(s.id) as jumlah_siswa'))
            ->groupBy('k.id', 'k.nama_kelas', 'w.nama')
            ->orderBy('k.nama_kelas')
            ->get();
        $headers = ['Kelas', 'Wali Kelas', 'Jumlah Siswa'];
        $rows = $data->map(fn ($r) => [$r->nama_kelas, $r->wali_kelas ?: '-', $r->jumlah_siswa]);

        return view('dashboard.pdf.official_table', ['title' => 'Rekap Wali Kelas', 'meta' => 'Daftar wali kelas dan jumlah siswa', 'headers' => $headers, 'rows' => $rows]);
    }

    public function admin(Request $request, $type)
    {
        wajibSuperadmin();

        $title = 'Laporan Admin';
        $meta = 'Dicetak oleh superadmin';
        $headers = [];
        $rows = collect();

        if ($type === 'siswa') {
            $title = 'Daftar Siswa';
            $headers = ['Nama', 'NIS', 'Username', 'Kelas', 'Orang Tua', 'No Orang Tua', 'Status'];
            $rows = User::where('role', 'siswa')->with('kelasRelasi')->orderBy('nama')->get()
                ->map(fn ($r) => [$r->nama, $r->nis ?: '-', $r->username, $r->kelasRelasi->nama_kelas ?? '-', $r->nama_ortu ?: '-', $r->no_ortu ?: '-', $r->aktif ? 'Aktif' : 'Nonaktif']);
        } elseif ($type === 'guru') {
            $title = 'Daftar Guru';
            $headers = ['Nama', 'NUPTK', 'Username', 'Status'];
            $rows = User::where('role', 'guru')->orderBy('nama')->get()
                ->map(fn ($r) => [$r->nama, $r->nuptk ?: '-', $r->username, $r->aktif ? 'Aktif' : 'Nonaktif']);
        } elseif ($type === 'wali-kelas') {
            return redirect('/dashboard/admin/rekap/wali-kelas-pdf');
        } elseif ($type === 'guru-piket') {
            $title = 'Data Guru Piket';
            $headers = ['Guru', 'Pengganti 1', 'Pengganti 2', 'Hari', 'Jam', 'Status', 'Aktif'];
            $rows = DB::table('guru_pikets as gp')->join('users as g', 'g.id', '=', 'gp.guru_id')->leftJoin('users as p1', 'p1.id', '=', 'gp.guru_pengganti_id')->leftJoin('users as p2', 'p2.id', '=', 'gp.guru_pengganti2_id')->whereNull('gp.deleted_at')->select('g.nama as guru', 'p1.nama as p1', 'p2.nama as p2', 'gp.*')->orderBy('gp.hari')->orderBy('gp.jam_mulai')->get()
                ->map(fn ($r) => [$r->guru, $r->p1 ?: '-', $r->p2 ?: '-', ucfirst($r->hari), $r->jam_mulai.' - '.$r->jam_selesai, $r->status, $r->aktif ? 'Ya' : 'Tidak']);
        } elseif ($type === 'kelas') {
            $title = 'Daftar Kelas';
            $headers = ['Kelas', 'Jurusan', 'Wali Kelas'];
            $rows = DB::table('kelas as k')->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')->whereNull('k.deleted_at')->select('k.nama_kelas', 'j.nama_jurusan', 'w.nama as wali')->orderBy('k.nama_kelas')->get()
                ->map(fn ($r) => [$r->nama_kelas, $r->nama_jurusan ?: '-', $r->wali ?: '-']);
        } elseif ($type === 'jurusan') {
            $title = 'Daftar Jurusan';
            $headers = ['Kode', 'Nama Jurusan'];
            $rows = DB::table('jurusan')->whereNull('deleted_at')->orderBy('kode_jurusan')->get()
                ->map(fn ($r) => [$r->kode_jurusan, $r->nama_jurusan]);
        } elseif ($type === 'tahun-ajaran') {
            $title = 'Daftar Tahun Ajaran';
            $headers = ['Nama', 'Semester', 'Tanggal Mulai', 'Tanggal Selesai', 'Aktif'];
            $rows = DB::table('tahun_ajarans')->whereNull('deleted_at')->orderByDesc('tanggal_mulai')->get()
                ->map(fn ($r) => [$r->nama, ucfirst($r->semester), $r->tanggal_mulai, $r->tanggal_selesai, $r->aktif ? 'Ya' : 'Tidak']);
        } elseif ($type === 'kalender') {
            $title = 'Kalender Sekolah';
            $headers = ['Mulai', 'Selesai', 'Judul', 'Jenis', 'Provinsi', 'Keterangan'];
            $rows = DB::table('kalender_sekolahs')->whereNull('deleted_at')->orderBy('tanggal_mulai')->get()
                ->map(fn ($r) => [$r->tanggal_mulai, $r->tanggal_selesai, $r->judul, $r->jenis, $r->provinsi ?: '-', $r->keterangan ?: '-']);
        } elseif ($type === 'jadwal') {
            $title = 'Jadwal Pelajaran';
            $headers = ['Hari', 'Jam', 'Kelas', 'Mapel', 'Guru', 'Pengganti', 'Status'];
            $rows = DB::table('jadwal_pelajarans as j')->join('kelas as k', 'k.id', '=', 'j.kelas_id')->join('mapels as m', 'm.id', '=', 'j.mapel_id')->join('users as g', 'g.id', '=', 'j.guru_id')->leftJoin('users as p', 'p.id', '=', 'j.guru_pengganti_id')->whereNull('j.deleted_at')->select('j.*', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as guru', 'p.nama as pengganti')->orderBy('j.hari')->orderBy('j.jam_mulai')->get()
                ->map(fn ($r) => [$r->hari, $r->jam_mulai.' - '.$r->jam_selesai, $r->nama_kelas, $r->nama_mapel, $r->guru, $r->pengganti ?: '-', $r->status_guru ?: '-']);
        } elseif ($type === 'audit-log') {
            $title = 'Audit Log';
            $headers = ['Waktu', 'User', 'Role', 'Aksi', 'Data', 'IP'];
            $rows = DB::table('audit_logs')->latest('id')->limit(500)->get()
                ->map(fn ($r) => [$r->created_at, $r->user_name ?: '-', $r->user_role ?: '-', $r->aksi, ($r->tabel ?: '-').' #'.($r->record_id ?: '-'), $r->ip_address ?: '-']);
        } elseif ($type === 'arsip') {
            $table = $request->get('table', 'users');
            abort_if(! array_key_exists($table, tabelBisaArsip()), 404);
            $title = 'Arsip Data '.(tabelBisaArsip()[$table] ?? $table);
            $headers = ['ID', 'Ringkasan', 'Diarsipkan'];
            $rows = DB::table($table)->whereNotNull('deleted_at')->latest('deleted_at')->limit(500)->get()
                ->map(fn ($r) => [$r->id, collect((array) $r)->except(['password', 'remember_token'])->map(fn ($v, $k) => $k.': '.$v)->implode(' | '), $r->deleted_at]);
        } elseif ($type === 'keamanan') {
            $title = 'Dashboard Keamanan';
            $headers = ['Waktu', 'Username', 'Event', 'Percobaan', 'IP', 'Keterangan'];
            $rows = DB::table('login_security_events')->latest('id')->limit(500)->get()
                ->map(fn ($r) => [$r->created_at, $r->username ?: '-', $r->event_type, $r->attempt_count, $r->ip_address ?: '-', $r->keterangan ?: '-']);
        } elseif ($type === 'pengajuan-izin') {
            $title = 'Pengajuan Izin/Sakit';
            $headers = ['Siswa', 'Kelas', 'Tanggal', 'Jenis', 'Status', 'Reviewer'];
            $rows = DB::table('student_permit_requests as p')->join('users as s', 's.id', '=', 'p.siswa_id')->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')->whereNull('p.deleted_at')->select('p.*', 's.nama as siswa', 'k.nama_kelas', 'r.nama as reviewer')->latest('p.id')->get()
                ->map(fn ($r) => [$r->siswa, $r->nama_kelas ?: '-', $r->tanggal_mulai.' s/d '.$r->tanggal_selesai, $r->jenis, $r->status, $r->reviewer ?: '-']);
        } elseif ($type === 'absensi-harian-crud') {
            $title = 'CRUD Absensi Harian';
            $headers = ['Tanggal', 'Siswa', 'Kelas', 'Masuk', 'Status Masuk', 'Pulang', 'Status Pulang'];
            $rows = DB::table('absensis as a')->join('users as s', 's.id', '=', 'a.id_siswa')->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')->whereNull('a.deleted_at')->select('a.*', 's.nama', 'k.nama_kelas')->latest('a.tanggal')->limit(1000)->get()
                ->map(fn ($r) => [$r->tanggal, $r->nama, $r->nama_kelas ?: '-', $r->jam_masuk ?: '-', $r->status_masuk ?: '-', $r->jam_pulang ?: '-', $r->status_pulang ?: '-']);
        } elseif ($type === 'absensi-mapel-crud') {
            return redirect('/dashboard/admin/rekap/absensi-mapel-pdf');
        } else {
            abort(404);
        }

        return view('dashboard.pdf.official_table', compact('title', 'meta', 'headers', 'rows'));
    }

    public function detailSiswa($id)
    {
        wajibSuperadmin();
        $data = detailProfilSiswaData((int) $id);
        $siswa = $data['siswa'];
        $headers = ['Bagian', 'Tanggal/Label', 'Keterangan', 'Status'];
        $rows = collect([
            ['Profil', 'Nama', $siswa->nama, ''],
            ['Profil', 'NIS', $siswa->nis ?: '-', ''],
            ['Profil', 'Kelas', $siswa->nama_kelas ?: '-', ''],
            ['Profil', 'Orang Tua', ($siswa->nama_ortu ?: '-').' / '.($siswa->no_ortu ?: '-'), ''],
        ]);
        foreach ($data['absensiHarian']->take(30) as $a) {
            $rows->push(['Absensi Harian', $a->tanggal, 'Masuk: '.($a->jam_masuk ?: '-').' | Pulang: '.($a->jam_pulang ?: '-'), ($a->status_masuk ?: '-').' / '.($a->status_pulang ?: '-')]);
        }
        foreach ($data['absensiMapel']->take(30) as $a) {
            $rows->push(['Absensi Mapel', $a->tanggal, ($a->nama_mapel ?: '-').' | Guru: '.($a->nama_guru ?: '-'), $a->status ?: '-']);
        }
        $title = 'Detail Profil Siswa';
        $meta = $siswa->nama.' - '.($siswa->nama_kelas ?: '-');

        return view('dashboard.pdf.official_table', compact('title', 'meta', 'headers', 'rows'));
    }
}
