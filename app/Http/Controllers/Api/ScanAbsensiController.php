<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Services\AttendanceSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScanAbsensiController extends Controller
{
    public function store(Request $request)
    {
        // Middleware API sudah membaca token dan menaruh data user ke atribut user_login.
        // Di sini sistem memastikan yang melakukan scan benar-benar siswa aktif.
        $user = $request->attributes->get('user_login');

        if (! $user || $user->role !== 'siswa' || ! $user->aktif || $user->deleted_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan',
            ]);
        }

        // Fungsi helper ini mengecek latitude dan longitude dari aplikasi.
        // Jika siswa berada di luar radius sekolah, helper langsung mengembalikan response error.
        if ($lokasiError = apiValidasiLokasiSekolah($request)) {
            return $lokasiError;
        }

        // Pengaman tambahan: jika aplikasi masih mengirim user_id, nilainya harus sama dengan user dari token.
        // Tujuannya agar siswa tidak bisa memakai token sendiri untuk mengirim absensi atas nama siswa lain.
        if ($request->filled('user_id') && (int) $request->user_id !== (int) $user->id) {
            return response()->json(['status' => 'error', 'message' => 'User tidak sesuai dengan token akses'], 403);
        }

        // Token QR dari aplikasi dicari di tabel qr_codes.
        // Untuk absensi harian, QR ini dibuat oleh guru piket.
        $qr = QrCode::where('token', $request->token)->first();

        if (! $qr) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR tidak valid',
            ]);
        }

        // Jika kolom aktif tersedia, QR yang sudah dinonaktifkan tidak boleh dipakai lagi.
        // Contohnya ketika petugas guru piket berubah dan QR lama harus dibatalkan.
        if (Schema::hasColumn('qr_codes', 'aktif') && ! (bool) $qr->aktif) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR sudah tidak aktif karena petugas guru piket telah berubah.',
            ], 422);
        }

        // Sistem mengecek kalender sekolah.
        // Jika hari ini libur, absensi tidak dibuka dan siswa tidak dihitung alfa.
        $hari = strtolower(now()->locale('id')->translatedFormat('l'));
        $libur = DB::table('kalender_sekolahs')
            ->where('jenis', 'libur')
            ->where(function ($query) use ($hari) {
                $query->where(function ($date) {
                    $date->whereDate('tanggal_mulai', '<=', now()->toDateString())
                        ->whereDate('tanggal_selesai', '>=', now()->toDateString());
                })->orWhere(function ($repeat) use ($hari) {
                    $repeat->where('berulang', 1)->where('hari_berulang', $hari);
                });
            })
            ->first();

        if ($libur) {
            return response()->json([
                'status' => 'error',
                'code' => 'hari_libur',
                'title' => 'Hari Ini Libur',
                'message' => 'Hari ini libur: '.$libur->judul.'. Absensi tidak dibuka dan tidak dihitung alfa.',
                'should_redirect' => true,
                'redirect_to' => 'dashboard',
                'route' => '/dashboard/users',
                'libur' => [
                    'judul' => $libur->judul,
                    'tanggal' => now()->toDateString(),
                ],
            ]);
        }

        // QR punya batas waktu berlaku.
        // Jika sudah lewat expires_at, siswa wajib meminta QR baru dari guru piket.
        if ($qr->expires_at && now()->greaterThan($qr->expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR sudah kedaluwarsa',
            ]);
        }

        // Transaksi dipakai agar proses cek dan simpan absensi aman dari scan ganda bersamaan.
        return DB::transaction(function () use ($request, $user, $qr) {
            // lockForUpdate mengunci baris absensi siswa hari ini.
            // Ini mencegah dua request scan masuk bersamaan membuat data dobel.
            $cek = Absensi::where('id_siswa', $user->id)
                ->whereDate('tanggal', now()->toDateString())
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Absen Masuk
            |--------------------------------------------------------------------------
            | Bagian ini dijalankan jika QR yang discan bertipe masuk.
            */
            if ($qr->tipe == 'masuk') {
                // Jika siswa sudah punya jam masuk, scan masuk kedua ditolak.
                if ($cek && $cek->jam_masuk) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Sudah absen masuk',
                    ]);
                }

                // Jam sekarang dibandingkan dengan batas telat dari pengaturan absensi.
                // Hasilnya menentukan status masuk: hadir atau telat.
                $jamSekarang = now()->format('H:i:s');
                $batasMasuk = AttendanceSettingService::batasTelat();
                $statusMasuk = 'hadir';

                if ($jamSekarang > $batasMasuk) {
                    $statusMasuk = 'telat';
                }

                // Tahun ajaran aktif disimpan agar rekap bisa difilter berdasarkan periode sekolah.
                $tahunAjaranId = DB::table('tahun_ajarans')->where('aktif', true)->value('id');

                // Jika belum ada data absensi hari ini, sistem membuat record baru.
                if (! $cek) {
                    Absensi::create([
                        'id_siswa' => $user->id,
                        'tahun_ajaran_id' => $tahunAjaranId,
                        'tanggal' => now()->toDateString(),
                        'jam_masuk' => $jamSekarang,
                        'status_masuk' => $statusMasuk,
                    ]);
                } else {
                    // Jika record sudah ada, sistem hanya melengkapi jam masuk dan status masuk.
                    $cek->update([
                        'tahun_ajaran_id' => $cek->tahun_ajaran_id ?: $tahunAjaranId,
                        'jam_masuk' => $jamSekarang,
                        'status_masuk' => $statusMasuk,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Absen Pulang
            |--------------------------------------------------------------------------
            | Bagian ini dijalankan jika QR yang discan bertipe pulang.
            */
            if ($qr->tipe == 'pulang') {
                // Siswa tidak boleh absen pulang sebelum absen masuk.
                if (! $cek) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Belum absen masuk',
                    ]);
                }

                // Jika jam pulang sudah ada, scan pulang kedua ditolak.
                if ($cek->jam_pulang) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Sudah absen pulang',
                    ]);
                }

                // Status pulang ditentukan dari jam scan.
                // Jika scan sebelum jam pulang resmi, statusnya pulang_cepat.
                $cek->update([
                    'tahun_ajaran_id' => $cek->tahun_ajaran_id ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id'),
                    'jam_pulang' => now()->format('H:i:s'),
                    'status_pulang' => now()->format('H:i:s') < AttendanceSettingService::jamPulang() ? 'pulang_cepat' : 'pulang',
                ]);
            }

            // Setelah absen masuk berhasil, sistem dapat membuat notifikasi untuk admin.
            if ($qr->tipe === 'masuk') {
                if ((DB::table('attendance_settings')->where('key', 'notif_absen_masuk_admin')->value('value') ?? '1') === '1') {
                    $kelasSiswa = DB::table('kelas')->where('id', $user->kelas_id)->value('nama_kelas');
                    apiBuatNotifikasiAdmin(
                        'absensi_masuk_siswa',
                        'Siswa Absen Masuk',
                        $user->nama.' sudah absen masuk pukul '.now()->format('H:i').' dengan status '.($statusMasuk ?? 'hadir').'.',
                        [
                            'source_id' => $user->id,
                            'siswa_id' => $user->id,
                            'siswa' => $user->nama,
                            'nis' => $user->nis,
                            'kelas' => $kelasSiswa ?: '-',
                            'jam' => now()->format('H:i:s'),
                            'status' => $statusMasuk ?? 'hadir',
                            'tanggal' => now()->toDateString(),
                        ],
                        ($statusMasuk ?? 'hadir') === 'telat' ? 'warning' : 'success'
                    );
                }

                // Notifikasi ini dikirim ke orang tua agar orang tua tahu anaknya sudah absen masuk.
                kirimNotifikasiOrangTua(
                    (int) $user->id,
                    'Absensi Masuk',
                    $user->nama.' sudah absen masuk jam '.now()->format('H:i').' dengan status '.($statusMasuk ?? 'hadir').'.',
                    ['tipe' => 'masuk', 'jam' => now()->format('H:i'), 'status' => $statusMasuk ?? 'hadir']
                );
            }

            // Jika yang discan adalah QR pulang, orang tua juga mendapat notifikasi pulang.
            if ($qr->tipe === 'pulang') {
                kirimNotifikasiOrangTua(
                    (int) $user->id,
                    'Absensi Pulang',
                    $user->nama.' sudah absen pulang jam '.now()->format('H:i').'.',
                    ['tipe' => 'pulang', 'jam' => now()->format('H:i')]
                );
            }

            // Response sukses dikirim ke aplikasi mobile setelah proses absensi selesai.
            return response()->json([
                'status' => 'success',
                'message' => 'Absensi berhasil',
            ]);
        });
    }
}
