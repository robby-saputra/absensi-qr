<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $user = session('user');

        $totalSiswa = User::where('role', 'siswa')->where('aktif', 1)->whereNull('deleted_at')->count();
        $totalSiswaNonaktif = User::where('role', 'siswa')->where('aktif', 0)->whereNull('deleted_at')->count();
        $totalGuru = User::where('role', 'guru')->where('aktif', 1)->whereNull('deleted_at')->count();
        $totalGuruNonaktif = User::where('role', 'guru')->where('aktif', 0)->whereNull('deleted_at')->count();
        $totalKelas = DB::table('kelas')->count();
        $totalJurusan = DB::table('jurusan')->count();

        $totalNotifikasi = DB::table('notifications')
            ->whereNull('user_id')
            ->where('status', 'belum_dibaca')
            ->count();
        buatNotifikasiKalenderBesok();
        buatNotifikasiBulanBelumDitutup();

        $tahunAjaranAktif = DB::table('tahun_ajarans')
            ->where('aktif', true)
            ->first();
        $kalenderHariIni = kalenderSekolahTanggal(now()->toDateString());
        $infoLiburHariIni = infoLiburHariIni('admin');

        $absensiHariIni = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->where('s.role', 'siswa')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereDate('a.tanggal', now()->toDateString());

        $totalMasukHariIni = (clone $absensiHariIni)
            ->whereNotNull('a.jam_masuk')
            ->count();

        $totalPulangHariIni = (clone $absensiHariIni)
            ->whereNotNull('a.jam_pulang')
            ->count();

        $totalBelumAbsen = max($totalSiswa - $totalMasukHariIni, 0);

        $siswaPerKelas = DB::table('kelas as k')
            ->leftJoin('users as s', function ($join) {
                $join->on('s.kelas_id', '=', 'k.id')
                    ->where('s.role', 'siswa')
                    ->where('s.aktif', 1);
            })
            ->select('k.nama_kelas', DB::raw('COUNT(s.id) as total'))
            ->groupBy('k.id', 'k.nama_kelas')
            ->orderBy('k.nama_kelas')
            ->get();

        $guruPiketAktif = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->count();

        $guruPiketTidakHadir = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->whereIn('status', ['Izin', 'Sakit'])
            ->count();

        $topSiswaTelat = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->where('s.role', 'siswa')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereDate('a.tanggal', '>=', now()->subDays(30)->toDateString())
            ->where('a.status_masuk', 'telat')
            ->select('s.id', 's.nama', 's.nis', 'k.nama_kelas', DB::raw('COUNT(*) as total'))
            ->groupBy('s.id', 's.nama', 's.nis', 'k.nama_kelas')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $topSiswaAlfa = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->where('s.role', 'siswa')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereDate('a.tanggal', '>=', now()->subDays(30)->toDateString())
            ->where(function ($query) {
                $query->whereIn('a.status_masuk', ['alfa', 'alpa'])
                    ->orWhereIn('a.status_pulang', ['alfa', 'alpa']);
            })
            ->select('s.id', 's.nama', 's.nis', 'k.nama_kelas', DB::raw('COUNT(*) as total'))
            ->groupBy('s.id', 's.nama', 's.nis', 'k.nama_kelas')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $pengajuanMenunggu = Schema::hasTable('student_permit_requests')
            ? DB::table('student_permit_requests')->where('status', 'menunggu')->whereNull('deleted_at')->count()
            : 0;

        $guruPiketTidakHadirList = DB::table('guru_pikets as gp')
            ->join('users as g', 'g.id', '=', 'gp.guru_id')
            ->leftJoin('users as p1', 'p1.id', '=', 'gp.guru_pengganti_id')
            ->leftJoin('users as p2', 'p2.id', '=', 'gp.guru_pengganti2_id')
            ->where('gp.hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('gp.aktif', 1)
            ->whereNull('gp.deleted_at')
            ->whereIn('gp.status', ['Izin', 'Sakit'])
            ->select('gp.*', 'g.nama as nama_guru', 'p1.nama as pengganti_1', 'p2.nama as pengganti_2')
            ->orderBy('gp.jam_mulai')
            ->limit(5)
            ->get();

        $perluPerhatian = [
            'telat' => $topSiswaTelat->sum('total'),
            'alfa' => $topSiswaAlfa->sum('total'),
            'pengajuan_menunggu' => $pengajuanMenunggu,
            'piket_tidak_hadir' => $guruPiketTidakHadir,
        ];

        $chartData = [
            'absensi' => [
                'labels' => ['Masuk', 'Pulang', 'Belum Absen'],
                'values' => [$totalMasukHariIni, $totalPulangHariIni, $totalBelumAbsen],
            ],
            'kelas' => [
                'labels' => $siswaPerKelas->pluck('nama_kelas')->values(),
                'values' => $siswaPerKelas->pluck('total')->values(),
            ],
            'piket' => [
                'labels' => ['Bertugas', 'Tidak Hadir'],
                'values' => [$guruPiketAktif, $guruPiketTidakHadir],
            ],
        ];

        return view('dashboard.admin', compact(
            'user',
            'totalSiswa',
            'totalSiswaNonaktif',
            'totalGuru',
            'totalGuruNonaktif',
            'totalKelas',
            'totalJurusan',
            'totalNotifikasi',
            'tahunAjaranAktif',
            'kalenderHariIni',
            'infoLiburHariIni',
            'totalMasukHariIni',
            'totalPulangHariIni',
            'totalBelumAbsen',
            'guruPiketAktif',
            'guruPiketTidakHadir',
            'topSiswaTelat',
            'topSiswaAlfa',
            'pengajuanMenunggu',
            'guruPiketTidakHadirList',
            'perluPerhatian',
            'chartData'
        ));
    }
}
