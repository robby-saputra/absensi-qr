<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Controller ini menyiapkan ringkasan utama untuk dashboard admin.
class AdminDashboardController extends Controller
{
    // Menampilkan total data master, ringkasan absensi hari ini, grafik, dan notifikasi admin.
    public function index()
    {
        $user = session('user');
        $now = now('Asia/Jakarta');
        $today = $now->toDateString();
        $hariIni = strtolower($now->locale('id')->translatedFormat('l'));

        // Menghitung data utama sekolah yang ditampilkan sebagai kartu ringkasan.
        $totalSiswa = User::where('role', 'siswa')->where('aktif', 1)->whereNull('deleted_at')->count();
        $totalGuru = User::where('role', 'guru')->where('aktif', 1)->whereNull('deleted_at')->count();
        $totalKelas = DB::table('kelas')->count();
        $totalJurusan = DB::table('jurusan')->count();
        $totalOrangTua = User::where('role', 'siswa')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNotNull('nama_ortu')
                    ->orWhereNotNull('no_ortu');
            })
            ->count();

        $tahunAjaranAktif = DB::table('tahun_ajarans')
            ->where('aktif', true)
            ->first();
        $kalenderHariIni = kalenderSekolahTanggal($today);
        $infoLiburHariIni = infoLiburHariIni('admin');

        // Query dasar absensi hari ini dipakai ulang untuk hitung masuk, pulang, dan belum absen.
        $absensiHariIni = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->where('s.role', 'siswa')
            ->where('s.aktif', 1)
            ->whereNull('s.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereDate('a.tanggal', $today);

        $totalMasukHariIni = (clone $absensiHariIni)
            ->whereNotNull('a.jam_masuk')
            ->count();

        $totalPulangHariIni = (clone $absensiHariIni)
            ->whereNotNull('a.jam_pulang')
            ->count();

        $totalBelumAbsen = max($totalSiswa - $totalMasukHariIni, 0);
        $totalIzinHariIni = (clone $absensiHariIni)
            ->where(function ($query) {
                $query->where('a.status_masuk', 'izin')
                    ->orWhere('a.status_pulang', 'izin');
            })
            ->distinct('a.id_siswa')
            ->count('a.id_siswa');
        $totalSakitHariIni = (clone $absensiHariIni)
            ->where(function ($query) {
                $query->where('a.status_masuk', 'sakit')
                    ->orWhere('a.status_pulang', 'sakit');
            })
            ->distinct('a.id_siswa')
            ->count('a.id_siswa');

        $qrAktifHariIni = 0;
        if (Schema::hasTable('qr_codes')) {
            $qrAktifHariIni += DB::table('qr_codes')
                ->whereDate('tanggal', $today)
                ->when(Schema::hasColumn('qr_codes', 'aktif'), fn ($query) => $query->where('aktif', true))
                ->count();
        }
        if (Schema::hasTable('qr_sesis')) {
            $qrAktifHariIni += DB::table('qr_sesis')
                ->whereDate('tanggal', $today)
                ->when(Schema::hasColumn('qr_sesis', 'aktif'), fn ($query) => $query->where('aktif', true))
                ->count();
        }

        $pengajuanMenunggu = Schema::hasTable('student_permit_requests')
            ? DB::table('student_permit_requests')
                ->where('status', 'menunggu')
                ->whereNull('deleted_at')
                ->count()
            : 0;

        $guruPiketAktif = Schema::hasTable('guru_pikets')
            ? DB::table('guru_pikets')
                ->where('hari', $hariIni)
                ->where('aktif', 1)
                ->whereNull('deleted_at')
                ->count()
            : 0;

        $guruPiketBelumVerifikasi = 0;
        if (Schema::hasTable('guru_pikets') && Schema::hasTable('guru_piket_statuses')) {
            $guruPiketBelumVerifikasi = DB::table('guru_pikets as gp')
                ->leftJoin('guru_piket_statuses as gps', function ($join) use ($today) {
                    $join->on('gps.guru_piket_id', '=', 'gp.id')
                        ->on('gps.guru_id', '=', 'gp.guru_id')
                        ->whereDate('gps.tanggal', $today)
                        ->whereNull('gps.deleted_at');
                })
                ->where('gp.hari', $hariIni)
                ->where('gp.aktif', 1)
                ->whereNull('gp.deleted_at')
                ->where(function ($query) {
                    $query->whereNull('gps.waktu_konfirmasi')
                        ->orWhere('gps.status', 'belum_konfirmasi');
                })
                ->count();
        }

        $guruMapelBelumVerifikasi = 0;
        if (Schema::hasTable('jadwal_pelajarans') && Schema::hasTable('jadwal_guru_statuses')) {
            $guruMapelBelumVerifikasi = DB::table('jadwal_pelajarans as j')
                ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($today) {
                    $join->on('jgs.jadwal_id', '=', 'j.id')
                        ->whereDate('jgs.tanggal', $today);
                })
                ->whereRaw('LOWER(j.hari) = ?', [$hariIni])
                ->whereNull('j.deleted_at')
                ->whereNull('jgs.status_dipilih_at')
                ->count();
        }

        $guruBelumVerifikasi = $guruPiketBelumVerifikasi + $guruMapelBelumVerifikasi;

        $guruPenggantiAktif = 0;
        if (Schema::hasTable('guru_piket_replacements')) {
            $guruPenggantiAktif += DB::table('guru_piket_replacements')
                ->whereDate('tanggal', $today)
                ->where('status_penugasan', 'aktif')
                ->whereNull('deleted_at')
                ->count();
        }
        if (Schema::hasTable('jadwal_guru_replacements')) {
            $guruPenggantiAktif += DB::table('jadwal_guru_replacements')
                ->whereDate('tanggal', $today)
                ->where('status_penugasan', 'aktif')
                ->whereNull('deleted_at')
                ->count();
        }

        // Data jumlah siswa per kelas dipakai untuk grafik distribusi kelas.
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

        $kelasMax = max((int) $siswaPerKelas->max('total'), 1);
        $kelasDistribusi = $siswaPerKelas->map(function ($kelas) use ($kelasMax) {
            return (object) [
                'nama_kelas' => $kelas->nama_kelas,
                'total' => (int) $kelas->total,
                'persen' => round(((int) $kelas->total / $kelasMax) * 100, 1),
            ];
        });

        $attendanceBase = max($totalSiswa, 1);
        $attendanceOverview = [
            ['label' => 'Masuk', 'value' => $totalMasukHariIni, 'percent' => round(($totalMasukHariIni / $attendanceBase) * 100), 'tone' => 'success', 'icon' => 'fa-user-check'],
            ['label' => 'Pulang', 'value' => $totalPulangHariIni, 'percent' => round(($totalPulangHariIni / $attendanceBase) * 100), 'tone' => 'primary', 'icon' => 'fa-person-walking-arrow-right'],
            ['label' => 'Belum Absen', 'value' => $totalBelumAbsen, 'percent' => round(($totalBelumAbsen / $attendanceBase) * 100), 'tone' => 'warning', 'icon' => 'fa-user-clock'],
            ['label' => 'Izin/Sakit', 'value' => $totalIzinHariIni + $totalSakitHariIni, 'percent' => round((($totalIzinHariIni + $totalSakitHariIni) / $attendanceBase) * 100), 'tone' => 'danger', 'icon' => 'fa-notes-medical'],
        ];

        $summaryCards = [
            ['label' => 'Siswa Aktif', 'value' => $totalSiswa, 'icon' => 'fa-user-graduate', 'tone' => 'teal', 'meta' => 'Akun siswa aktif'],
            ['label' => 'Guru Aktif', 'value' => $totalGuru, 'icon' => 'fa-chalkboard-user', 'tone' => 'navy', 'meta' => 'Guru dan pengajar'],
            ['label' => 'Total Kelas', 'value' => $totalKelas, 'icon' => 'fa-school', 'tone' => 'blue', 'meta' => 'Rombel terdaftar'],
            ['label' => 'Total Jurusan', 'value' => $totalJurusan, 'icon' => 'fa-layer-group', 'tone' => 'amber', 'meta' => 'Program keahlian'],
            ['label' => 'Orang Tua Aktif', 'value' => $totalOrangTua, 'icon' => 'fa-people-roof', 'tone' => 'slate', 'meta' => 'Kontak wali siswa'],
            ['label' => 'QR Aktif Hari Ini', 'value' => $qrAktifHariIni, 'icon' => 'fa-qrcode', 'tone' => 'green', 'meta' => 'Harian dan mapel'],
        ];

        $quickActions = [
            ['label' => 'Kelola Pengguna', 'url' => '/dashboard/admin/users', 'icon' => 'fa-users-gear', 'hint' => 'Akun admin, guru, siswa'],
            ['label' => 'Data Siswa', 'url' => '/dashboard/admin/siswa', 'icon' => 'fa-user-graduate', 'hint' => 'Profil dan kelas siswa'],
            ['label' => 'Jadwal Guru Piket', 'url' => '/dashboard/admin/guru-piket', 'icon' => 'fa-user-shield', 'hint' => 'Petugas piket harian'],
            ['label' => 'Monitoring Verifikasi', 'url' => '/dashboard/admin/monitoring-verifikasi-guru', 'icon' => 'fa-user-check', 'hint' => 'Status guru hari ini'],
            ['label' => 'Rekap Absensi', 'url' => '/dashboard/admin/absensi/rekap', 'icon' => 'fa-clipboard-list', 'hint' => 'Kehadiran harian siswa'],
            ['label' => 'Pengaturan Absensi', 'url' => '/dashboard/admin/pengaturan', 'icon' => 'fa-gear', 'hint' => 'Periode dan aturan sistem'],
            ['label' => 'Arsip Data', 'url' => '/dashboard/admin/arsip', 'icon' => 'fa-box-archive', 'hint' => 'Data terhapus dan restore'],
        ];

        $attentionItems = collect([
            (object) [
                'label' => 'Guru belum verifikasi',
                'value' => $guruBelumVerifikasi,
                'description' => $guruPiketBelumVerifikasi.' piket, '.$guruMapelBelumVerifikasi.' mapel',
                'url' => '/dashboard/admin/monitoring-verifikasi-guru',
                'tone' => $guruBelumVerifikasi > 0 ? 'warning' : 'success',
                'icon' => 'fa-user-clock',
            ],
            (object) [
                'label' => 'Pengajuan izin menunggu',
                'value' => $pengajuanMenunggu,
                'description' => 'Perlu keputusan admin',
                'url' => '/dashboard/admin/pengajuan-izin',
                'tone' => $pengajuanMenunggu > 0 ? 'warning' : 'success',
                'icon' => 'fa-file-circle-exclamation',
            ],
            (object) [
                'label' => 'Guru pengganti aktif',
                'value' => $guruPenggantiAktif,
                'description' => 'Piket dan mata pelajaran',
                'url' => '/dashboard/admin/monitoring-verifikasi-guru',
                'tone' => $guruPenggantiAktif > 0 ? 'info' : 'muted',
                'icon' => 'fa-user-plus',
            ],
            (object) [
                'label' => 'Siswa belum absen',
                'value' => $totalBelumAbsen,
                'description' => 'Dari '.$totalSiswa.' siswa aktif',
                'url' => '/dashboard/admin/absensi/rekap',
                'tone' => $totalBelumAbsen > 0 ? 'danger' : 'success',
                'icon' => 'fa-triangle-exclamation',
            ],
        ]);

        $chartData = [
            'absensi' => [
                'labels' => collect($attendanceOverview)->pluck('label')->values(),
                'values' => collect($attendanceOverview)->pluck('value')->values(),
            ],
            'kelas' => [
                'labels' => $siswaPerKelas->pluck('nama_kelas')->values(),
                'values' => $siswaPerKelas->pluck('total')->values(),
            ],
        ];

        $adminBellItems = collect();
        $adminBellUnread = 0;

        // Jika tabel notifikasi tersedia, admin mengambil notifikasi penting terbaru.
        if (Schema::hasTable('notifications')) {
            $adminBellQuery = DB::table('notifications')->whereNull('user_id');

            if (Schema::hasColumn('notifications', 'kategori')) {
                $adminBellQuery->whereIn('kategori', ['pengajuan_izin', 'absensi_masuk_siswa', 'absensi_siswa_diubah', 'guru_tidak_hadir', 'guru_pengganti_tidak_hadir', 'sistem']);
            }

            $adminBellItems = $adminBellQuery->latest('id')->limit(5)->get()->map(function ($item) {
                $payload = [];
                if (! empty($item->payload)) {
                    $payload = json_decode($item->payload, true) ?: [];
                }

                $item->action_url = match ($item->kategori ?? null) {
                    'guru_pengganti_tidak_hadir' => '/dashboard/admin/jadwal/edit/'.($item->source_id ?? ($payload['jadwal_id'] ?? '')),
                    'pengajuan_izin' => '/dashboard/admin/pengajuan-izin',
                    default => '/dashboard/admin/notifikasi?kategori='.($item->kategori ?? 'semua'),
                };

                if (($item->kategori ?? null) === 'guru_pengganti_tidak_hadir' && empty($item->source_id) && empty($payload['jadwal_id'])) {
                    $item->action_url = '/dashboard/admin/jadwal';
                }

                return $item;
            });

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
                    'action_url' => '/dashboard/admin/pengajuan-izin',
                ];
            });
        }

        return view('dashboard.admin', compact(
            'user',
            'totalSiswa',
            'totalGuru',
            'totalKelas',
            'totalJurusan',
            'totalOrangTua',
            'tahunAjaranAktif',
            'kalenderHariIni',
            'infoLiburHariIni',
            'totalMasukHariIni',
            'totalPulangHariIni',
            'totalBelumAbsen',
            'totalIzinHariIni',
            'totalSakitHariIni',
            'qrAktifHariIni',
            'pengajuanMenunggu',
            'guruPiketAktif',
            'guruBelumVerifikasi',
            'guruPenggantiAktif',
            'attendanceOverview',
            'summaryCards',
            'quickActions',
            'attentionItems',
            'kelasDistribusi',
            'chartData',
            'adminBellItems',
            'adminBellUnread'
        ));
    }
}
