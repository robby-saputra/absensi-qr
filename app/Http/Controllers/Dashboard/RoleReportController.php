<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleReportController extends Controller
{
    public function guruPdf(Request $request, $type)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());
        $headers = [];
        $rows = collect();
        $title = 'Rekap Guru Mapel';

        if ($type === 'jadwal') {
            $title = 'Rekap Jadwal Guru Mapel';
            $headers = ['Hari', 'Kelas', 'Mapel', 'Jam', 'Status'];
            $rows = DB::table('jadwal_pelajarans as j')
                ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
                ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                ->where('j.guru_id', $user->id)
                ->select('j.*', 'k.nama_kelas', 'm.nama_mapel')
                ->orderBy('j.hari')
                ->orderBy('j.jam_mulai')
                ->get()
                ->map(fn ($r) => [$r->hari, $r->nama_kelas, $r->nama_mapel, $r->jam_mulai.' - '.$r->jam_selesai, $r->status_guru ?: 'normal']);
        } else {
            $title = $type === 'siswa' ? 'Rekap Siswa Guru Mapel' : 'Rekap Absensi Mapel Guru';
            $headers = $type === 'siswa'
                ? ['Nama', 'NIS', 'Kelas']
                : ['Tanggal', 'Nama', 'Kelas', 'Mapel', 'Jam Scan', 'Status', 'Catatan'];
            if ($type === 'siswa') {
                $kelasIds = DB::table('jadwal_pelajarans')->where('guru_id', $user->id)->pluck('kelas_id')->unique();
                $rows = DB::table('users as s')->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')->where('s.role', 'siswa')->whereIn('s.kelas_id', $kelasIds)->select('s.nama', 's.nis', 'k.nama_kelas')->orderBy('k.nama_kelas')->orderBy('s.nama')->get()->map(fn ($r) => [$r->nama, $r->nis ?: '-', $r->nama_kelas ?: '-']);
            } else {
                $rows = DB::table('absensi_mapels as a')
                    ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
                    ->join('users as s', 's.id', '=', 'a.siswa_id')
                    ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                    ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
                    ->whereNull('a.deleted_at')
                    ->where('j.guru_id', $user->id)
                    ->whereDate('a.tanggal', $tanggal)
                    ->select('a.*', 's.nama', 'k.nama_kelas', 'm.nama_mapel')
                    ->orderBy('k.nama_kelas')
                    ->orderBy('s.nama')
                    ->get()
                    ->map(fn ($r) => [$r->tanggal, $r->nama, $r->nama_kelas ?: '-', $r->nama_mapel, $r->jam_scan ?: '-', $r->status ?: '-', $r->catatan_guru ?? '-']);
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
            ? DB::table('guru_pikets as gp')->join('users as g', 'g.id', '=', 'gp.guru_id')->whereNull('gp.deleted_at')->select('g.nama as guru', 'gp.*')->orderBy('gp.hari')->orderBy('gp.jam_mulai')->get()->map(fn ($r) => [$r->guru, $r->hari, $r->jam_mulai.' - '.$r->jam_selesai, $r->status ?: '-'])
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
        $catatan = Schema::hasTable('wali_followups') ? DB::table('wali_followups')->where('siswa_id', $siswaId)->where('wali_id', $user->id)->latest('tanggal')->limit(5)->get() : collect();
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
        $headers = ['Tanggal', 'Siswa', 'Kelas', 'Mapel', 'Jam Scan', 'Status', 'Catatan'];
        $rows = DB::table('absensi_mapels as a')
            ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
            ->join('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->whereNull('a.deleted_at')
            ->whereBetween('a.tanggal', [$mulai, $selesai])
            ->when($tahunAjaranId, fn ($q) => $q->where('a.tahun_ajaran_id', $tahunAjaranId))
            ->where('j.guru_id', $user->id)
            ->select('a.*', 's.nama', 'k.nama_kelas', 'm.nama_mapel')
            ->orderBy('a.tanggal')->orderBy('k.nama_kelas')->orderBy('s.nama')
            ->get()
            ->map(fn ($r) => [$r->tanggal, $r->nama, $r->nama_kelas ?: '-', $r->nama_mapel ?: '-', $r->jam_scan ?: '-', $r->status ?: '-', $r->catatan_guru ?? '-']);

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

    public function validasiTutupBulan(Request $request)
    {
        $user = session('user');
        [$mulai, $selesai, $bulan] = periodeBulan($request->get('bulan'));
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $kelasId = null;

        if ($user->role === 'guru') {
            $kelasId = DB::table('kelas')->where('wali_kelas_id', $user->id)->whereNull('deleted_at')->value('id');
            abort_if(! $kelasId, 403);
        } else {
            wajibSuperadmin();
            $kelasId = $request->get('kelas_id');
        }

        $hasil = validasiDataTutupBulan($mulai, $selesai, $kelasId ? (int) $kelasId : null, $tahunAjaranId ? (int) $tahunAjaranId : null);
        $statusBulanan = simpanStatusValidasiBulanan($bulan, $tahunAjaranId ? (int) $tahunAjaranId : null, $kelasId ? (int) $kelasId : null, $hasil, $request);
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

        return view('dashboard.validasi_tutup_bulan', compact('user', 'hasil', 'bulan', 'mulai', 'selesai', 'tahunAjaran', 'tahunAjaranId', 'kelas', 'kelasId', 'statusBulanan'));
    }

    public function kunciTutupBulan(Request $request)
    {
        $user = session('user');
        $request->validate([
            'bulan' => 'required|string',
            'tahun_ajaran_id' => 'nullable|integer|exists:tahun_ajarans,id',
            'kelas_id' => 'nullable|integer|exists:kelas,id',
            'catatan' => 'nullable|string|max:1000',
        ]);

        [$mulai, $selesai, $bulan] = periodeBulan($request->bulan);
        $kelasId = $request->kelas_id ? (int) $request->kelas_id : null;
        if ($user->role === 'guru') {
            $kelasId = DB::table('kelas')->where('wali_kelas_id', $user->id)->whereNull('deleted_at')->value('id');
            abort_if(! $kelasId, 403);
        } else {
            wajibSuperadmin();
        }

        $hasil = validasiDataTutupBulan($mulai, $selesai, $kelasId, $request->tahun_ajaran_id ? (int) $request->tahun_ajaran_id : null);
        $totalMasalah = collect($hasil)->sum(fn ($items) => $items->count());
        if ($totalMasalah > 0) {
            return back()->with('error', 'Bulan belum bisa dikunci karena masih ada '.$totalMasalah.' data yang perlu dibenahi.');
        }

        $status = simpanStatusValidasiBulanan($bulan, $request->tahun_ajaran_id ? (int) $request->tahun_ajaran_id : null, $kelasId, $hasil, $request);
        DB::table('monthly_validation_statuses')->where('id', $status->id)->update([
            'status' => 'dikunci',
            'locked_by' => $user->id,
            'locked_at' => now(),
            'catatan' => $request->catatan,
            'updated_at' => now(),
        ]);

        DB::table('rekap_locks')->updateOrInsert(
            ['jenis_rekap' => 'bulanan', 'tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai],
            ['locked_by' => $user->id, 'keterangan' => 'Laporan bulanan '.$bulan.' dikunci', 'locked_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
        AuditLogger::record('monthly_validation_lock', 'monthly_validation_statuses', (int) $status->id, 'Laporan bulanan dikunci', $status, DB::table('monthly_validation_statuses')->where('id', $status->id)->first(), $request);

        return back()->with('success', 'Laporan bulan '.$bulan.' berhasil dikunci.');
    }
}
