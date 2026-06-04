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
        $totalGuru = User::where('role', 'guru')->where('aktif', 1)->whereNull('deleted_at')->count();
        $totalKelas = DB::table('kelas')->count();
        $totalJurusan = DB::table('jurusan')->count();

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

        $chartData = [
            'absensi' => [
                'labels' => ['Masuk', 'Pulang', 'Belum Absen'],
                'values' => [$totalMasukHariIni, $totalPulangHariIni, $totalBelumAbsen],
            ],
            'kelas' => [
                'labels' => $siswaPerKelas->pluck('nama_kelas')->values(),
                'values' => $siswaPerKelas->pluck('total')->values(),
            ],
        ];

        $adminBellItems = collect();
        $adminBellUnread = 0;

        if (Schema::hasTable('notifications')) {
            $adminBellQuery = DB::table('notifications')->whereNull('user_id');

            if (Schema::hasColumn('notifications', 'kategori')) {
                $adminBellQuery->whereIn('kategori', ['pengajuan_izin', 'absensi_masuk_siswa', 'absensi_siswa_diubah', 'guru_tidak_hadir', 'sistem']);
            }

            $adminBellItems = $adminBellQuery->latest('id')->limit(5)->get();

            $adminBellUnreadQuery = DB::table('notifications')->whereNull('user_id');
            if (Schema::hasColumn('notifications', 'status')) {
                $adminBellUnreadQuery->where('status', 'belum_dibaca');
            }
            $adminBellUnread = $adminBellUnreadQuery->count();
        }

        if ($adminBellItems->isEmpty() && Schema::hasTable('student_permit_requests')) {
            $permitItems = DB::table('student_permit_requests as p')
                ->join('users as s', 's.id', '=', 'p.siswa_id')
                ->where('p.status', 'menunggu')
                ->select('p.id', 'p.jenis', 'p.tanggal_mulai', 'p.created_at', 's.nama')
                ->latest('p.id')
                ->limit(5)
                ->get();
            $adminBellUnread = $permitItems->count();
            $adminBellItems = $permitItems->map(function ($item) {
                return (object) [
                    'judul' => 'Pengajuan '.ucfirst($item->jenis).' Baru',
                    'pesan' => $item->nama.' mengajukan '.strtolower($item->jenis).' mulai '.$item->tanggal_mulai.'.',
                    'created_at' => $item->created_at,
                ];
            });
        }

        return view('dashboard.admin', compact(
            'user',
            'totalSiswa',
            'totalGuru',
            'totalKelas',
            'totalJurusan',
            'tahunAjaranAktif',
            'kalenderHariIni',
            'infoLiburHariIni',
            'totalMasukHariIni',
            'totalPulangHariIni',
            'totalBelumAbsen',
            'chartData',
            'adminBellItems',
            'adminBellUnread'
        ));
    }
}
