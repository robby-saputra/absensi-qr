<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WaliKelasDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $semesterFilter = $request->get('semester') ?: optional($tahunAjaran->firstWhere('id', $tahunAjaranId))->semester;

        // cek apakah guru ini wali kelas
        $wali = DB::table('kelas')
            ->select('id', 'nama_kelas')
            ->where('wali_kelas_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        // kalau bukan wali kelas
        if (! $wali) {
            abort(403, 'Akses ditolak');
        }

        $siswa = User::where('role', 'siswa')
            ->where('kelas_id', $wali->id)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get();
        $siswaNonaktifCount = User::where('role', 'siswa')
            ->where('kelas_id', $wali->id)
            ->where('aktif', 0)
            ->whereNull('deleted_at')
            ->count();

        foreach ($siswa as $item) {
            $item->nama_kelas = $wali->nama_kelas;
        }

        foreach ($siswa as $s) {
            $absen = DB::table('absensis')
                ->where('id_siswa', $s->id)
                ->whereDate('tanggal', now()->toDateString())
                ->whereNull('deleted_at')
                ->first();

            if ($absen) {
                $s->status_hari_ini = $absen->status_masuk;
            } else {
                $s->status_hari_ini = 'belum_absen';
            }
        }

        $analitik = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->where('s.kelas_id', $wali->id)
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->when($tahunAjaranId, fn ($q) => $q->where(function ($where) use ($tahunAjaranId) {
                $where->where('a.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('a.tahun_ajaran_id');
            }))
            ->whereDate('a.tanggal', '>=', now()->subDays(30)->toDateString())
            ->selectRaw("
            SUM(CASE WHEN a.jam_masuk IS NOT NULL AND COALESCE(a.status_masuk,'') NOT IN ('izin','sakit','alfa','alpa') THEN 1 ELSE 0 END) as hadir,
            SUM(CASE WHEN a.status_masuk = 'telat' THEN 1 ELSE 0 END) as telat,
            SUM(CASE WHEN a.status_masuk = 'izin' OR a.status_pulang = 'izin' THEN 1 ELSE 0 END) as izin,
            SUM(CASE WHEN a.status_masuk = 'sakit' OR a.status_pulang = 'sakit' THEN 1 ELSE 0 END) as sakit,
            SUM(CASE WHEN a.status_masuk IN ('alfa','alpa') OR a.status_pulang IN ('alfa','alpa') THEN 1 ELSE 0 END) as alfa
        ")
            ->first();

        $trenMingguan = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->where('s.kelas_id', $wali->id)
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->when($tahunAjaranId, fn ($q) => $q->where(function ($where) use ($tahunAjaranId) {
                $where->where('a.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('a.tahun_ajaran_id');
            }))
            ->whereDate('a.tanggal', '>=', now()->subDays(42)->toDateString())
            ->selectRaw('YEARWEEK(a.tanggal, 1) as pekan, COUNT(*) as total')
            ->groupBy('pekan')
            ->orderBy('pekan')
            ->get();

        $topRawan = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->where('s.kelas_id', $wali->id)
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->when($tahunAjaranId, fn ($q) => $q->where(function ($where) use ($tahunAjaranId) {
                $where->where('a.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('a.tahun_ajaran_id');
            }))
            ->whereDate('a.tanggal', '>=', now()->subDays(30)->toDateString())
            ->where(function ($query) {
                $query->whereIn('a.status_masuk', ['telat', 'alfa', 'alpa'])
                    ->orWhereIn('a.status_pulang', ['alfa', 'alpa']);
            })
            ->select('s.id', 's.nama', DB::raw('COUNT(*) as total'))
            ->groupBy('s.id', 's.nama')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        if ($topRawan->isNotEmpty()) {
            buatNotifikasiRoleHarian((int) $user->id, 'wali_siswa_rawan', 'Siswa Sering Telat/Alfa', $topRawan->count().' siswa kelas '.$wali->nama_kelas.' perlu perhatian wali kelas.', ['tanggal' => now()->toDateString(), 'kelas_id' => $wali->id]);
        }

        $isGuruMapelHariIni = DB::table('jadwal_pelajarans')
            ->where('hari', now()->locale('id')->isoFormat('dddd'))
            ->whereNull('deleted_at')
            ->where('guru_id', $user->id)
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
        $infoLiburHariIni = infoLiburHariIni('wali');

        return view('dashboard.wali', compact(
            'user',
            'siswa',
            'siswaNonaktifCount',
            'wali',
            'isGuruMapelHariIni',
            'isGuruPiketHariIni',
            'punyaAksesGuruPiket',
            'infoLiburHariIni',
            'analitik',
            'trenMingguan',
            'topRawan',
            'tahunAjaran',
            'tahunAjaranId',
            'semesterFilter'
        ));
    }

    public function siswa(Request $request)
    {
        $user = session('user');

        $wali = DB::table('kelas')
            ->select('id', 'nama_kelas')
            ->where('wali_kelas_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $wali) {
            abort(403);
        }

        $status = $request->get('status', 'aktif');
        $siswaQuery = User::where('role', 'siswa')
            ->where('kelas_id', $wali->id)
            ->whereNull('deleted_at');

        if ($status === 'nonaktif') {
            $siswaQuery->where('aktif', 0);
        } else {
            $siswaQuery->where('aktif', 1);
            $status = 'aktif';
        }

        $siswa = $siswaQuery->orderBy('nama')->get();
        $siswaNonaktifCount = User::where('role', 'siswa')
            ->where('kelas_id', $wali->id)
            ->where('aktif', 0)
            ->whereNull('deleted_at')
            ->count();

        foreach ($siswa as $item) {
            $item->nama_kelas = $wali->nama_kelas;
        }

        return view('dashboard.wali_siswa', compact(
            'user',
            'wali',
            'siswa',
            'status',
            'siswaNonaktifCount'
        ));
    }

    public function detailSiswa($id)
    {
        $user = session('user');

        $wali = DB::table('kelas')
            ->select('id', 'nama_kelas')
            ->where('wali_kelas_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $wali) {
            abort(403);
        }

        $target = User::where('role', 'siswa')
            ->where('kelas_id', $wali->id)
            ->whereNull('deleted_at')
            ->where('id', $id)
            ->first();

        if (! $target) {
            abort(403);
        }

        $data = detailProfilSiswaData((int) $id);
        $catatanWali = Schema::hasTable('wali_followups')
            ? DB::table('wali_followups')->where('siswa_id', $id)->where('wali_id', $user->id)->latest('tanggal')->limit(20)->get()
            : collect();
        $pengajuanSiswa = Schema::hasTable('student_permit_requests')
            ? DB::table('student_permit_requests')->where('siswa_id', $id)->whereNull('deleted_at')->latest('id')->limit(20)->get()
            : collect();
        $layout = 'wali';

        return view('dashboard.siswa.detail', $data + compact('user', 'layout', 'catatanWali', 'pengajuanSiswa'));
    }

    public function absensi(Request $request)
    {
        $user = session('user');

        $wali = DB::table('kelas')
            ->select('id', 'nama_kelas')
            ->where('wali_kelas_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $wali) {
            abort(403);
        }

        $tanggal = $request->get('tanggal');
        $bulan = $request->get('bulan', now()->format('m'));
        $tahun = $request->get('tahun', now()->format('Y'));
        $status = $request->get('status');
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $semesterFilter = $request->get('semester') ?: optional($tahunAjaran->firstWhere('id', $tahunAjaranId))->semester;

        $absensi = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->whereNull('a.deleted_at')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->select(
                'a.*',
                's.nama',
                'k.nama_kelas'
            )
            ->where('s.kelas_id', $wali->id)
            ->when($tahunAjaranId, fn ($q) => $q->where(function ($where) use ($tahunAjaranId) {
                $where->where('a.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('a.tahun_ajaran_id');
            }))
            ->when($tanggal, fn ($query) => $query->whereDate('a.tanggal', $tanggal))
            ->when(! $tanggal && $bulan, fn ($query) => $query->whereMonth('a.tanggal', $bulan))
            ->when(! $tanggal && $tahun, fn ($query) => $query->whereYear('a.tanggal', $tahun))
            ->when($status, function ($query) use ($status) {
                $query->where(function ($where) use ($status) {
                    $where->where('a.status_masuk', $status)
                        ->orWhere('a.status_pulang', $status);
                });
            })
            ->latest('a.id')
            ->limit(300)
            ->get();

        $ringkasan = [
            'hadir' => $absensi->filter(fn ($row) => $row->jam_masuk && ! in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa']))->count(),
            'telat' => $absensi->where('status_masuk', 'telat')->count(),
            'izin' => $absensi->filter(fn ($row) => in_array($row->status_masuk, ['izin']) || in_array($row->status_pulang, ['izin']))->count(),
            'sakit' => $absensi->filter(fn ($row) => in_array($row->status_masuk, ['sakit']) || in_array($row->status_pulang, ['sakit']))->count(),
            'alfa' => $absensi->filter(fn ($row) => in_array($row->status_masuk, ['alfa', 'alpa']) || in_array($row->status_pulang, ['alfa', 'alpa']))->count(),
        ];

        $siswaRawan = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->where('s.kelas_id', $wali->id)
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereDate('a.tanggal', '>=', now()->subDays(30)->toDateString())
            ->where(function ($query) {
                $query->whereIn('a.status_masuk', ['telat', 'izin', 'sakit', 'alfa', 'alpa'])
                    ->orWhereIn('a.status_pulang', ['izin', 'sakit', 'alfa', 'alpa']);
            })
            ->select('s.id', 's.nama', DB::raw('COUNT(*) as total_temuan'))
            ->groupBy('s.id', 's.nama')
            ->orderByDesc('total_temuan')
            ->limit(10)
            ->get();

        return view('dashboard.wali_absensi', compact(
            'user',
            'wali',
            'absensi',
            'tanggal',
            'bulan',
            'tahun',
            'status',
            'ringkasan',
            'siswaRawan',
            'tahunAjaran',
            'tahunAjaranId',
            'semesterFilter'
        ));
    }

    public function simpanCatatan(Request $request, $id)
    {
        $user = session('user');
        $request->validate([
            'tanggal' => 'required|date',
            'kategori' => 'required|string|max:50',
            'catatan' => 'required|string|max:1000',
        ]);

        $wali = DB::table('kelas')->where('wali_kelas_id', $user->id)->whereNull('deleted_at')->first();
        abort_if(! $wali, 403);
        $siswa = siswaAktifQuery()->where('kelas_id', $wali->id)->findOrFail($id);

        $newId = DB::table('wali_followups')->insertGetId([
            'wali_id' => $user->id,
            'siswa_id' => $siswa->id,
            'tanggal' => $request->tanggal,
            'kategori' => $request->kategori,
            'catatan' => $request->catatan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        AuditLogger::record('create', 'wali_followups', (int) $newId, 'Catatan pembinaan wali kelas dibuat', null, DB::table('wali_followups')->where('id', $newId)->first(), $request);

        return back()->with('success', 'Catatan pembinaan siswa berhasil disimpan.');
    }
}
