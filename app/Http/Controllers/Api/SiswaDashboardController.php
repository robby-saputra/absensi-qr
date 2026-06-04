<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Models\User;
use App\Services\AttendanceSettingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiswaDashboardController extends Controller
{
    public function index($siswa_id)
    {
    $user = User::where('role', 'siswa')->find($siswa_id);
    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
    }

    $tanggal = now()->toDateString();
    $hari = strtolower(now()->locale('id')->translatedFormat('l'));
    $absensi = DB::table('absensis')->where('id_siswa', $user->id)->whereDate('tanggal', $tanggal)->first();
    $kelas = DB::table('kelas as k')
        ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
        ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')
        ->where('k.id', $user->kelas_id)
        ->select('k.id', 'k.nama_kelas', 'j.nama_jurusan', 'w.nama as wali_kelas')
        ->first();

    $jadwalHariIni = DB::table('jadwal_pelajarans as jp')
        ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
        ->join('users as g', 'g.id', '=', 'jp.guru_id')
        ->leftJoin('absensi_mapels as am', function ($join) use ($user, $tanggal) {
            $join->on('am.jadwal_id', '=', 'jp.id')
                ->where('am.siswa_id', $user->id)
                ->whereDate('am.tanggal', $tanggal)
                ->whereNull('am.deleted_at');
        })
        ->where('jp.kelas_id', $user->kelas_id)
        ->whereRaw('LOWER(jp.hari) = ?', [$hari])
        ->whereNull('jp.deleted_at')
        ->select(
            'jp.id',
            'jp.hari',
            'jp.jam_mulai',
            'jp.jam_selesai',
            'jp.status_guru',
            'jp.alasan_tidak_hadir',
            'm.nama_mapel',
            'g.nama as guru_utama',
            'am.id as absensi_mapel_id',
            'am.jam_scan',
            'am.status as status_absen',
            'am.catatan_guru'
        )
        ->orderBy('jp.jam_mulai')
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'hari' => $item->hari,
                'jam_mulai' => substr((string) $item->jam_mulai, 0, 5),
                'jam_selesai' => substr((string) $item->jam_selesai, 0, 5),
                'nama_mapel' => $item->nama_mapel,
                'guru_utama' => $item->guru_utama,
                'status_guru' => $item->status_guru ?: 'normal',
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

    $settings = DB::table('attendance_settings')
        ->whereIn('key', ['nama_sekolah', 'logo_sekolah'])
        ->pluck('value', 'key');

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

    return response()->json([
        'status' => 'success',
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
        'kartu_pelajar' => [
            'kode' => 'SISWA-'.$user->id.'-'.($user->nis ?: $user->username),
            'nama_sekolah' => $settings['nama_sekolah'] ?? 'Sekolah',
            'logo_url' => url($settings['logo_sekolah'] ?? 'img/logo-ba.png'),
        ],
    ]);
    }
}
