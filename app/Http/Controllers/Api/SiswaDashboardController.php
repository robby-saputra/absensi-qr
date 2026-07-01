<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Services\ActiveTeachingTeacherResolver;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiswaDashboardController extends Controller
{
    // Endpoint ini menyusun seluruh data dashboard siswa untuk aplikasi mobile.
    public function index(Request $request, $siswa_id)
    {
    // Dashboard API ini dipakai aplikasi Android siswa dan orang tua.
    // Data yang dikembalikan berisi profil siswa, absensi, jadwal, pengajuan, kalender, dan notifikasi.
    $authenticatedUser = $request->attributes->get('user_login');
    $apiRole = (string) $request->attributes->get('api_role', $authenticatedUser?->role ?? '');

    // Akses dibatasi agar siswa/orang tua hanya bisa membuka data siswa yang sesuai dengan token.
    if (! $this->canAccessStudent($authenticatedUser, $apiRole, (int) $siswa_id)) {
        return response()->json(['status' => 'error', 'message' => 'Akses data siswa ditolak'], 403);
    }

    // Data siswa diambil dari tabel users dengan role siswa.
    $user = User::where('role', 'siswa')->find($siswa_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
    }

    // Waktu terpercaya dipakai agar seluruh perhitungan tanggal mengikuti zona Asia/Jakarta.
    $now = apiTrustedDateTime()->setTimezone('Asia/Jakarta');
    $tanggal = $now->toDateString();
    $hari = $this->hariIndonesia($now->format('l'));
    $hariAliases = $this->hariAliases($hari);

    // Tahun ajaran aktif dipakai untuk memfilter jadwal dan rekap.
    $tahunAjaranAktifId = Schema::hasTable('tahun_ajarans')
        ? DB::table('tahun_ajarans')->where('aktif', true)->value('id')
        : null;

    // Absensi harian hari ini dipakai untuk ringkasan status siswa.
    $absensi = DB::table('absensis')->where('id_siswa', $user->id)->whereDate('tanggal', $tanggal)->first();

    // Data kelas, jurusan, dan wali kelas dikirim agar dashboard mobile menampilkan identitas lengkap.
    $kelas = DB::table('kelas as k')
        ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
        ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')
        ->where('k.id', $user->kelas_id)
        ->select('k.id', 'k.nama_kelas', 'j.nama_jurusan', 'w.nama as wali_kelas')
        ->first();

    // Resolver guru aktif dipakai agar jadwal menampilkan guru utama atau guru pengganti yang benar.
    $resolver = app(ActiveTeachingTeacherResolver::class);

    // Query ini mengambil jadwal mapel hari ini untuk kelas siswa sekaligus status absensi mapelnya.
    $jadwalRows = DB::table('jadwal_pelajarans as jp')
        ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
        ->join('users as g', 'g.id', '=', 'jp.guru_id')
        ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggal) {
            $join->on('jgs.jadwal_id', '=', 'jp.id')
                ->whereDate('jgs.tanggal', $tanggal);
        })
        ->leftJoin('absensi_mapels as am', function ($join) use ($user, $tanggal) {
            $join->on('am.jadwal_id', '=', 'jp.id')
                ->where('am.siswa_id', $user->id)
                ->whereDate('am.tanggal', $tanggal)
                ->whereNull('am.deleted_at');
        })
        ->where('jp.kelas_id', $user->kelas_id)
        ->whereIn(DB::raw('LOWER(jp.hari)'), $hariAliases)
        ->when($tahunAjaranAktifId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), function ($query) use ($tahunAjaranAktifId) {
            $query->where(function ($yearQuery) use ($tahunAjaranAktifId) {
                $yearQuery->where('jp.tahun_ajaran_id', $tahunAjaranAktifId)
                    ->orWhereNull('jp.tahun_ajaran_id');
            });
        })
        ->whereNull('jp.deleted_at')
        ->select(
            'jp.id',
            'jp.guru_id as guru_utama_id',
            'jp.hari',
            'jp.jam_mulai',
            'jp.jam_selesai',
            'jp.jam_ke_mulai',
            'jp.jumlah_jp',
            DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
            'jgs.alasan_tidak_hadir',
            'm.nama_mapel',
            'g.nama as guru_utama',
            'am.id as absensi_mapel_id',
            'am.jam_scan',
            'am.status as status_absen',
            'am.catatan_guru'
        )
        ->orderBy('jp.jam_mulai')
        ->get();

    // Semua jadwal diselesaikan guru aktifnya dalam satu proses agar lebih konsisten dan efisien.
    $resolvedTeachers = $resolver->resolveMany($jadwalRows->map(function ($item) {
        $item->guru_id = $item->guru_utama_id;

        return $item;
    }), $tanggal);

    // Data jadwal diubah menjadi format array yang mudah dibaca aplikasi Android.
    $jadwalHariIni = $jadwalRows
        ->map(function ($item) use ($tanggal, $resolver, $resolvedTeachers) {
            $penugasan = $resolvedTeachers->get((int) $item->id);
            $activeReplacement = $penugasan?->active_replacement;
            $latestReplacement = $penugasan?->latest_replacement;
            $roleGuruAktif = $penugasan?->active_teacher_role;
            $guruAktif = $penugasan?->active_teacher_name;
            $butuhPengganti = (bool) ($penugasan?->needs_replacement ?? false);
            $guruTersedia = ! empty($guruAktif);
            $jpLabel = $this->jpLabel($item->jam_ke_mulai, $item->jumlah_jp);
            $statusPenugasan = $activeReplacement?->status_penugasan
                ?? $latestReplacement?->status_penugasan;
            $keteranganGuru = $this->teacherDescription(
                $guruAktif,
                $roleGuruAktif,
                $item->guru_utama,
                $penugasan?->primary_status ?: 'normal',
                $butuhPengganti
            );

            return [
                'id' => (int) $item->id,
                'tanggal' => $tanggal,
                'hari' => $item->hari,
                'jam_mulai' => substr((string) $item->jam_mulai, 0, 5),
                'jam_selesai' => substr((string) $item->jam_selesai, 0, 5),
                'jam_ke_mulai' => $item->jam_ke_mulai ? (int) $item->jam_ke_mulai : null,
                'jumlah_jp' => $item->jumlah_jp ? (int) $item->jumlah_jp : null,
                'jp_label' => $jpLabel,
                'nama_mapel' => $item->nama_mapel,
                'guru_utama_id' => (int) $item->guru_utama_id,
                'guru_utama' => $item->guru_utama,
                'status_guru' => $penugasan?->primary_status ?: ($item->status_guru ?: 'normal'),
                'status_guru_utama' => $penugasan?->primary_status ?: ($item->status_guru ?: 'normal'),
                'status_guru_utama_label' => $resolver->statusLabel($penugasan?->primary_status ?: ($item->status_guru ?: 'normal')),
                'alasan_guru_utama' => $item->alasan_tidak_hadir,
                'guru_aktif_id' => $penugasan?->active_teacher_id ? (int) $penugasan->active_teacher_id : null,
                'guru_aktif' => $guruAktif,
                'role_guru_aktif' => $roleGuruAktif,
                'role_guru_aktif_label' => $penugasan?->active_teacher_role_label,
                'peran_guru_aktif' => str_starts_with((string) $roleGuruAktif, 'pengganti') ? 'guru_pengganti' : $roleGuruAktif,
                'pengganti_terbaru_id' => $latestReplacement?->guru_pengganti_id ? (int) $latestReplacement->guru_pengganti_id : null,
                'pengganti_terbaru' => $latestReplacement?->nama_pengganti,
                'urutan_pengganti' => $activeReplacement?->urutan_penggantian
                    ? (int) $activeReplacement->urutan_penggantian
                    : ($latestReplacement?->urutan_penggantian ? (int) $latestReplacement->urutan_penggantian : null),
                'status_penugasan' => $statusPenugasan,
                'status_penugasan_label' => $statusPenugasan ? $resolver->statusLabel($statusPenugasan) : null,
                'status_penugasan_pengganti' => $statusPenugasan,
                'butuh_pengganti' => $butuhPengganti,
                'membutuhkan_pengganti' => $butuhPengganti,
                'guru_tersedia' => $guruTersedia,
                'keterangan_guru' => $keteranganGuru,
                'riwayat_pengganti' => ($penugasan?->chain ?? collect())->map(fn ($pengganti) => [
                    'guru_id' => (int) $pengganti->guru_pengganti_id,
                    'nama' => $pengganti->nama_pengganti,
                    'urutan' => (int) $pengganti->urutan_penggantian,
                    'status_penugasan' => $pengganti->status_penugasan,
                    'status_penugasan_label' => $resolver->statusLabel($pengganti->status_penugasan),
                    'status_kehadiran' => $pengganti->status_kehadiran,
                ])->values(),
                'alasan_tidak_hadir' => $item->alasan_tidak_hadir,
                'sudah_absen' => $item->absensi_mapel_id ? true : false,
                'jam_scan' => $item->jam_scan ? substr((string) $item->jam_scan, 0, 5) : null,
                'status_absen' => $item->status_absen ?: 'belum',
                'catatan_guru' => $item->catatan_guru,
            ];
        });

    $totalMapel = $jadwalHariIni->count();
    $sudahMapel = $jadwalHariIni->where('sudah_absen', true)->count();
    $kalenderHariIni = apiKalenderSiswa($tanggal, $tanggal);
    $liburHariIni = $kalenderHariIni->first(fn ($event) => ($event['jenis'] ?? null) === 'libur');
    $kalenderMendatang = apiKalenderSiswa(
        now()->startOfMonth()->toDateString(),
        now()->copy()->endOfMonth()->toDateString()
    );

    $pengajuan = DB::table('student_permit_requests as p')
        ->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')
        ->where('p.siswa_id', $user->id)
        ->whereNull('p.deleted_at')
        ->select(
            'p.id',
            'p.tanggal_mulai',
            'p.tanggal_selesai',
            'p.jenis',
            'p.alasan',
            'p.bukti_path',
            'p.status',
            'p.catatan_review',
            'p.reviewed_at',
            'p.created_at',
            'r.nama as reviewer'
        )
        ->latest('p.id')
        ->limit(10)
        ->get()
        ->map(function ($item) {
            $item->bukti_url = $item->bukti_path ? url('storage/'.$item->bukti_path) : null;

            return $item;
        });

    $notifikasi = collect();
    $batasAbsenMasuk = AttendanceSettingService::batasTelat();
    $labelBatasAbsenMasuk = substr((string) $batasAbsenMasuk, 0, 5);
    $izinHariIni = $pengajuan->first(function ($item) use ($tanggal) {
        return $item->status === 'disetujui'
            && $item->tanggal_mulai <= $tanggal
            && $item->tanggal_selesai >= $tanggal;
    });
    $ringkasanKehadiran = $this->attendanceSummary($absensi, $izinHariIni, $liburHariIni);
    $statistikBulan = $this->monthlyAttendanceStats((int) $user->id, $now);
    $aktivitasTerbaru = $this->recentActivities((int) $user->id, $now);

    if (! $liburHariIni) {
        $jamSekarang = now()->format('H:i');
        $mapelAktif = $jadwalHariIni->first(fn ($item) => $jamSekarang >= $item['jam_mulai'] && $jamSekarang <= $item['jam_selesai']);
        if ($mapelAktif) {
            $notifikasi->push([
                'kategori' => 'absensi_mapel',
                'judul' => 'Mapel Sedang Berlangsung',
                'pesan' => 'Sekarang '.$mapelAktif['nama_mapel'].' pukul '.$mapelAktif['jam_mulai'].'–'.$mapelAktif['jam_selesai'].'. Jangan lupa absen mapel.',
                'status' => 'belum_dibaca',
                'warna' => 'purple',
                'payload' => ['tipe' => 'pengingat_mapel', 'jadwal_id' => $mapelAktif['id']],
            ]);
        }

        if ($jamSekarang >= '14:00' && ! ($absensi && $absensi->jam_pulang)) {
            $notifikasi->push([
                'kategori' => 'absensi_harian',
                'judul' => 'Waktunya Absen Pulang',
                'pesan' => 'Sudah pukul 14.00. Waktunya melakukan scan QR untuk absen pulang.',
                'status' => 'belum_dibaca',
                'warna' => 'blue',
                'payload' => ['tipe' => 'pengingat_pulang', 'jam' => '14:00'],
            ]);
        }
    }

    if ($liburHariIni) {
        $notifikasi->push([
            'kategori' => 'kalender',
            'judul' => 'Hari Ini Libur',
            'pesan' => $liburHariIni['judul'].'. Absensi hari ini tidak dihitung alfa.',
            'status' => 'belum_dibaca',
            'warna' => 'red',
            'payload' => [
                'tipe' => 'libur',
                'status' => 'libur',
                'tanggal' => $tanggal,
                'judul' => $liburHariIni['judul'],
            ],
        ]);
    } elseif (! ($absensi && $absensi->jam_masuk)) {
        $statusAbsensi = strtolower((string) ($absensi->status_masuk ?? $absensi->status_pulang ?? ''));
        $jenisIzin = strtolower((string) ($izinHariIni->jenis ?? ''));

        if (in_array($statusAbsensi, ['izin', 'sakit'], true) || in_array($jenisIzin, ['izin', 'sakit'], true)) {
            $jenis = in_array($statusAbsensi, ['izin', 'sakit'], true) ? $statusAbsensi : $jenisIzin;
            $notifikasi->push([
                'kategori' => 'absensi_harian',
                'judul' => 'Anak '.ucfirst($jenis),
                'pesan' => $user->nama.' hari ini tercatat '.ucfirst($jenis).'.',
                'status' => 'belum_dibaca',
                'warna' => 'blue',
                'payload' => [
                    'tipe' => $jenis,
                    'status' => $jenis,
                    'jam' => '-',
                    'batas_absen' => $labelBatasAbsenMasuk,
                ],
            ]);
        } elseif (now()->format('H:i:s') > $batasAbsenMasuk) {
            $notifikasi->push([
                'kategori' => 'absensi_harian',
                'judul' => 'Anak Alfa',
                'pesan' => $user->nama.' belum absen masuk sampai batas waktu '.$labelBatasAbsenMasuk.', sehingga sementara dinyatakan Alfa.',
                'status' => 'belum_dibaca',
                'warna' => 'red',
                'payload' => [
                    'tipe' => 'alfa',
                    'status' => AttendanceSettingService::statusDefaultAlfa(),
                    'jam' => '-',
                    'batas_absen' => $labelBatasAbsenMasuk,
                ],
            ]);
        } else {
            $notifikasi->push([
                'kategori' => 'absensi_harian',
                'judul' => 'Belum Absen Masuk',
                'pesan' => $user->nama.' belum absen masuk. Batas absen masuk pukul '.$labelBatasAbsenMasuk.'.',
                'status' => 'belum_dibaca',
                'warna' => 'orange',
                'payload' => [
                    'tipe' => 'masuk',
                    'status' => 'belum_absen',
                    'jam' => '-',
                    'batas_absen' => $labelBatasAbsenMasuk,
                ],
            ]);
        }
    }

    if (! $liburHariIni && $absensi && $absensi->jam_masuk && ! $absensi->jam_pulang) {
        $notifikasi->push([
            'kategori' => 'absensi_harian',
            'judul' => 'Belum Absen Pulang',
            'pesan' => 'Jangan lupa scan QR pulang sebelum pulang sekolah.',
            'status' => 'belum_dibaca',
            'warna' => 'blue',
        ]);
    }

    if (! $liburHariIni && max(0, $totalMapel - $sudahMapel) > 0) {
        $notifikasi->push([
            'kategori' => 'absensi_mapel',
            'judul' => 'Belum Absen Mapel',
            'pesan' => max(0, $totalMapel - $sudahMapel).' sesi mapel hari ini belum discan.',
            'status' => 'belum_dibaca',
            'warna' => 'purple',
        ]);
    }

    foreach ($pengajuan->whereIn('status', ['disetujui', 'ditolak'])->take(3) as $item) {
        $notifikasi->push([
            'kategori' => 'pengajuan_izin',
            'judul' => 'Pengajuan '.ucfirst($item->status),
            'pesan' => 'Pengajuan '.$item->jenis.' tanggal '.$item->tanggal_mulai.' sampai '.$item->tanggal_selesai.' '.$item->status.'.',
            'status' => 'belum_dibaca',
            'warna' => $item->status === 'disetujui' ? 'green' : 'red',
        ]);
    }

    foreach ($kalenderHariIni as $event) {
        if ($liburHariIni && ($event['jenis'] ?? null) === 'libur') {
            continue;
        }

        $notifikasi->push([
            'kategori' => 'kalender',
            'judul' => 'Hari Ini '.ucfirst($event['jenis']),
            'pesan' => $event['judul'].($event['keterangan'] ? ' - '.$event['keterangan'] : ''),
            'status' => 'belum_dibaca',
            'warna' => $event['jenis'] === 'libur' ? 'red' : ($event['jenis'] === 'ujian' ? 'orange' : 'blue'),
        ]);
    }

    DB::table('notifications')
        ->where('user_id', $user->id)
        ->where('kategori', 'orang_tua')
        ->where('created_at', '<', now()->subDays(30))
        ->delete();

    $notifikasiOrangTua = DB::table('notifications')
        ->where('user_id', $user->id)
        ->where('kategori', 'orang_tua')
        ->latest('id')
        ->limit(10)
        ->get()
        ->map(function ($item) {
            $payload = json_decode($item->payload ?? '[]', true);

            return [
                'id' => $item->id,
                'judul' => $item->judul,
                'pesan' => $item->pesan,
                'kategori' => $item->kategori,
                'status' => $item->status,
                'warna' => 'blue',
                'payload' => is_array($payload) ? $payload : [],
                'created_at' => $item->created_at,
            ];
        });

    $notifikasiMonitoringOrangTua = $notifikasi
        ->filter(fn ($item) => in_array(($item['kategori'] ?? null), ['absensi_harian', 'kalender'], true))
        ->map(function ($item, $index) use ($tanggal) {
            return [
                'id' => 'monitoring-'.$tanggal.'-'.$index,
                'judul' => $item['judul'],
                'pesan' => $item['pesan'],
                'kategori' => 'orang_tua',
                'status' => $item['status'] ?? 'belum_dibaca',
                'warna' => $item['warna'] ?? 'blue',
                'payload' => $item['payload'] ?? [
                    'tipe' => $item['kategori'] ?? 'absensi',
                    'status' => $item['warna'] ?? 'info',
                    'jam' => '-',
                ],
                'created_at' => now()->toDateTimeString(),
            ];
        })
        ->values();

    $notifikasiOrangTua = $notifikasiMonitoringOrangTua
        ->merge($notifikasiOrangTua)
        ->take(10)
        ->values();
    $notifikasiPenting = $this->importantNotifications($absensi, $jadwalHariIni, $pengajuan, $ringkasanKehadiran, $now);

    return response()->json([
        'status' => 'success',
        'server_time' => apiTrustedDateTime()->toIso8601String(),
        'server_date' => $tanggal,
        'server_timezone' => 'Asia/Jakarta',
        'timezone' => 'Asia/Jakarta',
        'tanggal' => $tanggal,
        'hari' => ucfirst($hari),
        'batas_absen_masuk' => $labelBatasAbsenMasuk,
        'siswa' => [
            'id' => $user->id,
            'nama' => $user->nama,
            'nis' => $user->nis,
            'username' => $user->username,
            'kelas' => $kelas->nama_kelas ?? '-',
            'jurusan' => $kelas->nama_jurusan ?? '-',
            'wali_kelas' => $kelas->wali_kelas ?? '-',
            'nama_ortu' => $user->nama_ortu ?: '-',
            'no_ortu' => $user->no_ortu ?: '-',
            'status_akun' => $user->aktif ? 'Aktif' : 'Nonaktif',
        ],
        'absensi_hari_ini' => [
            'sudah_masuk' => $absensi && $absensi->jam_masuk ? true : false,
            'jam_masuk' => $absensi->jam_masuk ?? null,
            'status_masuk' => $absensi->status_masuk ?? null,
            'sudah_pulang' => $absensi && $absensi->jam_pulang ? true : false,
            'jam_pulang' => $absensi->jam_pulang ?? null,
            'status_pulang' => $absensi->status_pulang ?? null,
            'status_siswa' => $liburHariIni ? 'libur' : ($absensi->status_masuk ?? $absensi->status_pulang ?? 'belum_absen'),
            'catatan_piket' => $absensi->catatan_piket ?? null,
            'hari_libur' => $liburHariIni ? true : false,
            'keterangan_libur' => $liburHariIni['judul'] ?? null,
        ],
        'ringkasan_kehadiran_hari_ini' => $ringkasanKehadiran,
        'statistik_bulan_berjalan' => $statistikBulan,
        'aktivitas_terbaru' => $aktivitasTerbaru,
        'notifikasi_penting' => $notifikasiPenting,
        'scan_control' => [
            'scan_masuk_enabled' => ! $liburHariIni,
            'scan_pulang_enabled' => ! $liburHariIni,
            'scan_mapel_enabled' => ! $liburHariIni,
            'auto_close_seconds' => $liburHariIni ? 5 : null,
            'should_redirect' => $liburHariIni ? true : false,
            'redirect_to' => 'dashboard',
            'title' => $liburHariIni ? 'Hari Ini Libur' : null,
            'message' => $liburHariIni
                ? 'Hari ini libur: '.$liburHariIni['judul'].'. Absensi tidak dibuka dan tidak dihitung alfa.'
                : null,
        ],
        'status_mapel_hari_ini' => [
            'total' => $totalMapel,
            'sudah_absen' => $sudahMapel,
            'belum_absen' => max(0, $totalMapel - $sudahMapel),
            'label' => $totalMapel === 0
                ? 'Tidak ada jadwal mapel hari ini'
                : ($sudahMapel.'/'.$totalMapel.' mapel sudah absen'),
        ],
        'jadwal_hari_ini' => $jadwalHariIni,
        'pengajuan' => $pengajuan,
        'notifikasi' => $notifikasi->values(),
        'notifikasi_orang_tua' => $notifikasiOrangTua,
        'kalender_hari_ini' => $kalenderHariIni,
        'kalender_bulan_ini' => $kalenderMendatang,
    ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    // Mengecek apakah user yang login boleh membuka data siswa tertentu.
    private function canAccessStudent(?User $authenticatedUser, string $apiRole, int $siswaId): bool
    {
        if (! $authenticatedUser || (int) $authenticatedUser->id !== $siswaId) {
            return false;
        }

        return in_array($apiRole, ['siswa', 'orang_tua'], true);
    }

    // Mengubah nama hari bahasa Inggris dari Carbon menjadi nama hari bahasa Indonesia.
    private function hariIndonesia(string $englishDay): string
    {
        return [
            'monday' => 'senin',
            'tuesday' => 'selasa',
            'wednesday' => 'rabu',
            'thursday' => 'kamis',
            'friday' => 'jumat',
            'saturday' => 'sabtu',
            'sunday' => 'minggu',
        ][strtolower($englishDay)] ?? strtolower($englishDay);
    }

    // Menyiapkan variasi nama hari agar pencarian jadwal tetap cocok walaupun format huruf berbeda.
    private function hariAliases(string $hari): array
    {
        $aliases = [
            'senin' => ['senin', 'monday'],
            'selasa' => ['selasa', 'tuesday'],
            'rabu' => ['rabu', 'wednesday'],
            'kamis' => ['kamis', 'thursday'],
            'jumat' => ['jumat', 'jum\'at', 'friday'],
            'sabtu' => ['sabtu', 'saturday'],
            'minggu' => ['minggu', 'ahad', 'sunday'],
        ];

        return $aliases[$hari] ?? [$hari];
    }

    // Menyusun keterangan guru bertugas yang mudah dibaca oleh siswa di aplikasi.
    private function teacherDescription(?string $guruAktif, ?string $role, string $guruUtama, string $primaryStatus, bool $needsReplacement): string
    {
        if ($guruAktif && $role === 'guru_utama') {
            return 'Guru bertugas: '.$guruAktif;
        }

        if ($guruAktif && str_starts_with((string) $role, 'pengganti')) {
            return $guruAktif.' bertugas menggantikan '.$guruUtama;
        }

        if ($needsReplacement) {
            return 'Menunggu guru pengganti';
        }

        return $primaryStatus === 'normal'
            ? 'Guru bertugas: '.$guruUtama
            : 'Guru bertugas belum ditentukan';
    }

    // Mengubah data jam pelajaran menjadi label singkat seperti JP 1-2.
    private function jpLabel($jamKeMulai, $jumlahJp): ?string
    {
        $mulai = (int) ($jamKeMulai ?: 0);
        $jumlah = (int) ($jumlahJp ?: 0);
        if ($mulai <= 0 || $jumlah <= 0) {
            return null;
        }

        $akhir = $mulai + $jumlah - 1;

        return $mulai === $akhir ? 'JP '.$mulai : 'JP '.$mulai.'-'.$akhir;
    }

    // Menentukan ringkasan status absensi harian siswa untuk ditampilkan di dashboard.
    private function attendanceSummary(?object $absensi, ?object $izinHariIni, $liburHariIni): array
    {
        $status = strtolower((string) ($absensi->status_masuk ?? $absensi->status_pulang ?? ''));
        $jenisIzin = strtolower((string) ($izinHariIni->jenis ?? ''));

        if ($liburHariIni) {
            return [
                'status' => 'libur',
                'label' => 'Libur',
                'jam_masuk' => null,
                'deskripsi' => 'Hari ini libur sekolah',
            ];
        }

        if (in_array($status, ['izin', 'sakit', 'alfa', 'alpa'], true)) {
            $label = $status === 'alpa' ? 'Alpa' : ucfirst($status);
            return [
                'status' => $status,
                'label' => $label,
                'jam_masuk' => null,
                'deskripsi' => $label,
            ];
        }

        if (! $absensi?->jam_masuk && in_array($jenisIzin, ['izin', 'sakit'], true)) {
            return [
                'status' => $jenisIzin,
                'label' => ucfirst($jenisIzin),
                'jam_masuk' => null,
                'deskripsi' => ucfirst($jenisIzin),
            ];
        }

        if ($absensi?->jam_masuk) {
            $isLate = in_array($status, ['telat', 'terlambat'], true);
            $label = $isLate ? 'Terlambat' : 'Hadir';
            $jam = substr((string) $absensi->jam_masuk, 0, 5);
            return [
                'status' => $isLate ? 'terlambat' : 'hadir',
                'label' => $label,
                'jam_masuk' => $jam,
                'deskripsi' => $label.' pukul '.$jam,
            ];
        }

        return [
            'status' => 'belum_absen',
            'label' => 'Belum Absen',
            'jam_masuk' => null,
            'deskripsi' => 'Belum Absen',
        ];
    }

    // Menghitung statistik absensi siswa selama bulan berjalan.
    private function monthlyAttendanceStats(int $studentId, $now): array
    {
        $start = $now->copy()->startOfMonth()->toDateString();
        $end = $now->copy()->endOfMonth()->toDateString();
        $rows = DB::table('absensis')
            ->where('id_siswa', $studentId)
            ->whereBetween('tanggal', [$start, $end])
            ->whereNull('deleted_at')
            ->selectRaw("
                SUM(CASE WHEN jam_masuk IS NOT NULL AND COALESCE(status_masuk, '') NOT IN ('izin','sakit','alfa','alpa','telat','terlambat') THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status_masuk IN ('telat','terlambat') THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status_masuk = 'izin' OR status_pulang = 'izin' THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN status_masuk = 'sakit' OR status_pulang = 'sakit' THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN status_masuk IN ('alfa','alpa') OR status_pulang IN ('alfa','alpa') THEN 1 ELSE 0 END) as alpa
            ")
            ->first();

        return [
            'periode' => $now->format('Y-m'),
            'hadir' => (int) ($rows->hadir ?? 0),
            'terlambat' => (int) ($rows->terlambat ?? 0),
            'izin' => (int) ($rows->izin ?? 0),
            'sakit' => (int) ($rows->sakit ?? 0),
            'alpa' => (int) ($rows->alpa ?? 0),
        ];
    }

    // Mengambil aktivitas terbaru siswa dari absensi harian dan absensi mapel.
    private function recentActivities(int $studentId, $now): array
    {
        $start = $now->copy()->startOfMonth()->toDateString();
        $daily = DB::table('absensis')
            ->where('id_siswa', $studentId)
            ->whereDate('tanggal', '>=', $start)
            ->whereNull('deleted_at')
            ->latest('tanggal')
            ->limit(20)
            ->get()
            ->flatMap(function ($row) {
                $items = [];
                if ($row->jam_masuk) {
                    $items[] = [
                        'tipe' => 'masuk',
                        'label' => 'Masuk sekolah pukul '.substr((string) $row->jam_masuk, 0, 5),
                        'tanggal' => $row->tanggal,
                        'jam' => substr((string) $row->jam_masuk, 0, 5),
                        'sort_at' => $row->tanggal.' '.substr((string) $row->jam_masuk, 0, 8),
                    ];
                }
                if ($row->jam_pulang) {
                    $items[] = [
                        'tipe' => 'pulang',
                        'label' => 'Pulang sekolah pukul '.substr((string) $row->jam_pulang, 0, 5),
                        'tanggal' => $row->tanggal,
                        'jam' => substr((string) $row->jam_pulang, 0, 5),
                        'sort_at' => $row->tanggal.' '.substr((string) $row->jam_pulang, 0, 8),
                    ];
                }
                return $items;
            });

        $mapel = DB::table('absensi_mapels as am')
            ->leftJoin('jadwal_pelajarans as jp', 'jp.id', '=', 'am.jadwal_id')
            ->leftJoin('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->where('am.siswa_id', $studentId)
            ->whereDate('am.tanggal', '>=', $start)
            ->whereNull('am.deleted_at')
            ->select('am.tanggal', 'am.jam_scan', 'am.status', 'm.nama_mapel')
            ->latest('am.tanggal')
            ->limit(20)
            ->get()
            ->filter(fn ($row) => $row->jam_scan)
            ->map(fn ($row) => [
                'tipe' => 'mapel',
                'label' => 'Absensi '.($row->nama_mapel ?: 'Mapel').' pukul '.substr((string) $row->jam_scan, 0, 5),
                'tanggal' => $row->tanggal,
                'jam' => substr((string) $row->jam_scan, 0, 5),
                'status' => $row->status,
                'mapel' => $row->nama_mapel,
                'sort_at' => $row->tanggal.' '.substr((string) $row->jam_scan, 0, 8),
            ]);

        return $daily
            ->merge($mapel)
            ->sortByDesc('sort_at')
            ->take(5)
            ->values()
            ->map(fn ($item) => collect($item)->except('sort_at')->all())
            ->all();
    }

    // Menyusun notifikasi penting untuk siswa berdasarkan kondisi absensi, jadwal, dan pengajuan izin.
    private function importantNotifications(?object $absensi, $jadwalHariIni, $pengajuan, array $ringkasan, $now): array
    {
        $items = [];
        $status = strtolower((string) ($ringkasan['status'] ?? ''));
        if ($status === 'belum_absen') {
            $items[] = [
                'level' => 'warning',
                'pesan' => 'Perhatian: Anak belum melakukan absensi masuk hari ini.',
            ];
        } elseif (in_array($status, ['terlambat', 'alfa', 'alpa'], true)) {
            $items[] = [
                'level' => 'danger',
                'pesan' => $status === 'terlambat'
                    ? 'Perhatian: Anak terlambat masuk hari ini.'
                    : 'Perhatian: Anak berstatus alpa hari ini.',
            ];
        }

        $current = $now->format('H:i');
        foreach ($jadwalHariIni as $jadwal) {
            if ($current >= $jadwal['jam_mulai'] && $current <= $jadwal['jam_selesai'] && ! $jadwal['sudah_absen']) {
                $items[] = [
                    'level' => 'warning',
                    'pesan' => $jadwal['nama_mapel'].' sedang berlangsung, tetapi absensi mapel belum tercatat.',
                ];
                break;
            }
        }

        $belumMapel = $jadwalHariIni->where('sudah_absen', false)->count();
        if ($belumMapel > 0) {
            $items[] = [
                'level' => 'info',
                'pesan' => $belumMapel.' jadwal mapel hari ini belum tercatat absensinya.',
            ];
        }

        $pendingPermit = $pengajuan->first(fn ($item) => $item->status === 'menunggu');
        if ($pendingPermit) {
            $items[] = [
                'level' => 'warning',
                'pesan' => 'Ada pengajuan '.ucfirst($pendingPermit->jenis).' yang belum dikonfirmasi.',
            ];
        }

        if (empty($items)) {
            $items[] = [
                'level' => 'safe',
                'pesan' => 'Tidak ada pemberitahuan penting hari ini.',
            ];
        }

        return $items;
    }
}
