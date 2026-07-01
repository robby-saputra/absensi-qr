<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Services\SubjectAttendanceTeacherService;
use App\Services\TeachingPeriodService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScanMapelController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Middleware API sudah membaca token dan menaruh data user ke atribut user_login.
            // Scan mapel hanya boleh dilakukan oleh siswa aktif.
            $user = $request->attributes->get('user_login');

            if (! $user || $user->role !== 'siswa' || ! $user->aktif || $user->deleted_at) {
                return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan']);
            }

            // Pengaman tambahan agar user_id dari request tidak bisa dipalsukan.
            // Nilai user_id harus sama dengan siswa yang berasal dari token API.
            if ($request->filled('user_id') && (int) $request->user_id !== (int) $user->id) {
                return response()->json(['status' => 'error', 'message' => 'User tidak sesuai dengan token akses'], 403);
            }

            // Validasi lokasi sekolah memastikan siswa scan dari area yang diperbolehkan.
            // Jika lokasi di luar radius sekolah, helper mengembalikan response error.
            if ($lokasiError = apiValidasiLokasiSekolah($request)) {
                return $lokasiError;
            }

            // QR mapel disimpan pada tabel qr_sesis karena QR ini khusus untuk sesi pelajaran.
            $qr = DB::table('qr_sesis')->where('token', $request->token)->first();

            if (! $qr) {
                return response()->json(['status' => 'error', 'message' => 'QR Mapel tidak valid']);
            }

            // Kalender sekolah dicek agar siswa tidak bisa scan mapel pada hari libur.
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
                    'message' => 'Hari ini libur: '.$libur->judul.'. Absensi mapel tidak dibuka dan tidak dihitung alfa.',
                    'should_redirect' => true,
                    'redirect_to' => 'dashboard',
                    'route' => '/dashboard/users',
                    'libur' => [
                        'judul' => $libur->judul,
                        'tanggal' => now()->toDateString(),
                    ],
                ]);
            }

            // QR mapel hanya berlaku untuk tanggal saat QR dibuat.
            // Ini mencegah QR lama dipakai ulang pada hari lain.
            if ((string) $qr->tanggal !== now()->toDateString()) {
                return response()->json(['status' => 'error', 'message' => 'QR Mapel bukan untuk hari ini']);
            }

            // expires_at membatasi masa aktif QR mapel.
            // Jika sudah kedaluwarsa, siswa harus menunggu guru membuat QR baru.
            if (($qr->expires_at ?? null) && now()->greaterThan($qr->expires_at)) {
                return response()->json(['status' => 'error', 'message' => 'QR Mapel sudah kedaluwarsa']);
            }

            // Kolom aktif menandakan sesi QR masih dibuka.
            // Jika guru menutup sesi atau QR dinonaktifkan, scan ditolak.
            if ($qr->aktif != 1) {
                return response()->json(['status' => 'error', 'message' => 'QR sesi sudah ditutup']);
            }

            // Data jadwal dicari berdasarkan jadwal_id dari QR.
            // Jadwal ini menjadi dasar validasi kelas, hari, guru, dan tahun ajaran.
            $jadwal = DB::table('jadwal_pelajarans as j')
                ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'j.tahun_ajaran_id')
                ->where('j.id', $qr->jadwal_id)
                ->whereNull('j.deleted_at')
                ->select('j.*', 'ta.aktif as tahun_ajaran_aktif', 'ta.semester')
                ->first();

            // QR mapel hanya boleh discan oleh siswa dari kelas yang sesuai dengan jadwal.
            if (! $jadwal || (int) $jadwal->kelas_id !== (int) $user->kelas_id) {
                return response()->json(['status' => 'error', 'message' => 'QR Mapel tidak sesuai dengan kelas siswa']);
            }

            // Sistem memastikan jadwal memang aktif pada hari ini dan tahun ajarannya aktif.
            $hari = strtolower(now()->locale('id')->translatedFormat('l'));
            if (strtolower((string) $jadwal->hari) !== $hari || ! (bool) ($jadwal->tahun_ajaran_aktif ?? false)) {
                return response()->json(['status' => 'error', 'message' => 'Jadwal Mapel tidak aktif untuk hari ini']);
            }

            // Sebelum absen mapel, siswa wajib sudah absen masuk harian.
            // Ini menjaga urutan proses: hadir di sekolah dulu, baru absen pelajaran.
            $absensiHarian = Absensi::where('id_siswa', $user->id)
                ->whereDate('tanggal', now()->toDateString())
                ->whereNull('deleted_at')
                ->first();

            if (! $absensiHarian || ! $absensiHarian->jam_masuk) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Absensi harian belum tercatat. Silakan scan QR masuk terlebih dahulu.',
                ]);
            }

            // Service ini menentukan guru yang benar-benar aktif pada tanggal scan.
            // Guru aktif bisa guru utama atau guru pengganti jika guru utama izin/sakit.
            $teacherService = app(SubjectAttendanceTeacherService::class);
            $teacherState = $teacherService->resolve($jadwal, now('Asia/Jakarta')->toDateString());

            if (! $teacherState->guru_tersedia) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jadwal mapel belum memiliki guru aktif. Silakan tunggu penugasan guru pengganti.',
                ], 422);
            }

            // Transaksi dipakai agar cek duplikasi dan simpan absensi mapel berjalan aman.
            return DB::transaction(function () use ($user, $qr, $jadwal, $teacherService, $teacherState) {
                // lockForUpdate mencegah dua scan mapel yang masuk bersamaan membuat data ganda.
                $cek = DB::table('absensi_mapels')
                    ->where('siswa_id', $user->id)
                    ->where('jadwal_id', $qr->jadwal_id)
                    ->whereDate('tanggal', now()->toDateString())
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if ($cek) {
                    return response()->json(['status' => 'error', 'message' => 'Sudah absen mapel ini']);
                }

                // Tahun ajaran dari jadwal ikut disimpan agar rekap mapel bisa difilter per periode.
                $jadwalTahunAjaranId = $jadwal->tahun_ajaran_id;

                // TeachingPeriodService menentukan status hadir atau terlambat berdasarkan jam mulai pelajaran.
                $statusMapel = app(TeachingPeriodService::class)->attendanceStatus(now(), $jadwal->jam_mulai);

                // Data absensi mapel disimpan bersama payload guru pelaksana.
                // Payload tersebut berisi guru utama/guru pengganti yang benar-benar bertugas.
                DB::table('absensi_mapels')->insert([
                    'tahun_ajaran_id' => $jadwalTahunAjaranId,
                    'jadwal_id' => $qr->jadwal_id,
                    'siswa_id' => $user->id,
                    'tanggal' => now()->toDateString(),
                    'jam_scan' => now()->format('H:i:s'),
                    'status' => $statusMapel,
                    'created_at' => now(),
                    'updated_at' => now(),
                ] + $teacherService->payload($jadwal, now('Asia/Jakarta')->toDateString()));

                // Data mapel dan guru utama diambil lagi untuk isi notifikasi dan response API.
                $jadwal = DB::table('jadwal_pelajarans as j')
                    ->leftJoin('mapels as m', 'm.id', '=', 'j.mapel_id')
                    ->leftJoin('users as gu', 'gu.id', '=', 'j.guru_id')
                    ->where('j.id', $qr->jadwal_id)
                    ->select('m.nama_mapel', 'j.jam_mulai', 'j.jam_selesai', 'gu.nama as guru_utama')
                    ->first();

                // Orang tua diberi notifikasi ketika anaknya berhasil absen mapel.
                kirimNotifikasiOrangTua(
                    (int) $user->id,
                    'Absensi Mapel',
                    $user->nama.' sudah absen mapel '.($jadwal->nama_mapel ?? '-').' jam '.now()->format('H:i').'.',
                    ['tipe' => 'mapel', 'mapel' => $jadwal->nama_mapel ?? '-', 'jam' => now()->format('H:i')]
                );

                // Response sukses juga mengembalikan informasi guru pelaksana.
                // Ini membantu aplikasi menampilkan apakah yang bertugas guru utama atau pengganti.
                return response()->json([
                    'status' => 'success',
                    'message' => 'Absensi mapel berhasil',
                    'guru_utama' => $jadwal->guru_utama ?? null,
                    'guru_utama_id' => $teacherState->guru_utama_id,
                    'guru_pelaksana' => $teacherState->guru_pelaksana,
                    'guru_pelaksana_id' => $teacherState->guru_pelaksana_id,
                    'role_guru_pelaksana' => $teacherState->role_guru_pelaksana,
                ]);
            });
        } catch (Exception $e) {
            // Jika ada error tidak terduga, detail error dicatat di log server.
            // Aplikasi mobile hanya menerima pesan umum agar informasi teknis tidak bocor ke pengguna.
            Log::error('Scan mapel gagal', ['user_id' => optional($request->attributes->get('user_login'))->id, 'exception' => $e]);

            return response()->json(['status' => 'error', 'message' => 'Absensi mapel gagal diproses. Silakan coba kembali.'], 500);
        }
    }
}
