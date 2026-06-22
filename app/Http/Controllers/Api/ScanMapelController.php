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
use Illuminate\Support\Facades\Log;
use App\Services\TeachingPeriodService;

class ScanMapelController extends Controller
{
    public function store(Request $request)
    {
    try {
        $user = $request->attributes->get('user_login');
        if (! $user || $user->role !== 'siswa' || ! $user->aktif || $user->deleted_at) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan']);
        }
        if ($request->filled('user_id') && (int) $request->user_id !== (int) $user->id) {
            return response()->json(['status' => 'error', 'message' => 'User tidak sesuai dengan token akses'], 403);
        }
        if ($lokasiError = apiValidasiLokasiSekolah($request)) {
            return $lokasiError;
        } $qr = DB::table('qr_sesis')->where('token', $request->token)->first();
        if (! $qr) {
            return response()->json(['status' => 'error', 'message' => 'QR Mapel tidak valid']);
        } $hari = strtolower(now()->locale('id')->translatedFormat('l'));
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
        if ((string) $qr->tanggal !== now()->toDateString()) {
            return response()->json(['status' => 'error', 'message' => 'QR Mapel bukan untuk hari ini']);
        }
        if (($qr->expires_at ?? null) && now()->greaterThan($qr->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'QR Mapel sudah kedaluwarsa']);
        } /* |-------------------------------------------------------------------------- | CEK QR AKTIF |-------------------------------------------------------------------------- */ if ($qr->aktif != 1) {
            return response()->json(['status' => 'error', 'message' => 'QR sesi sudah ditutup']);
        }

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'j.tahun_ajaran_id')
            ->where('j.id', $qr->jadwal_id)->whereNull('j.deleted_at')
            ->select('j.*', 'ta.aktif as tahun_ajaran_aktif', 'ta.semester')->first();
        if (! $jadwal || (int) $jadwal->kelas_id !== (int) $user->kelas_id) {
            return response()->json(['status' => 'error', 'message' => 'QR Mapel tidak sesuai dengan kelas siswa']);
        }
        $hari = strtolower(now()->locale('id')->translatedFormat('l'));
        if (strtolower((string) $jadwal->hari) !== $hari || ! (bool) ($jadwal->tahun_ajaran_aktif ?? false)) {
            return response()->json(['status' => 'error', 'message' => 'Jadwal Mapel tidak aktif untuk hari ini']);
        }

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

        return DB::transaction(function () use ($user, $qr, $jadwal) {
        /* |-------------------------------------------------------------------------- | CEK DOUBLE ABSEN |-------------------------------------------------------------------------- */ $cek = DB::table('absensi_mapels')->where('siswa_id', $user->id)->where('jadwal_id', $qr->jadwal_id)->whereDate('tanggal', now()->toDateString())->whereNull('deleted_at')->lockForUpdate()->first();
        if ($cek) {
            return response()->json(['status' => 'error', 'message' => 'Sudah absen mapel ini']);
        }

        /* |--------------------------------------------------------------------------
        | SIMPAN ABSENSI MAPEL
        |-------------------------------------------------------------------------- */
        $jadwalTahunAjaranId = $jadwal->tahun_ajaran_id;

        $statusMapel = app(TeachingPeriodService::class)->attendanceStatus(now(), $jadwal->jam_mulai);

        DB::table('absensi_mapels')->insert([
            'tahun_ajaran_id' => $jadwalTahunAjaranId,
            'jadwal_id' => $qr->jadwal_id,
            'siswa_id' => $user->id,
            'tanggal' => now()->toDateString(),
            'jam_scan' => now()->format('H:i:s'),
            'status' => $statusMapel,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->leftJoin('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->where('j.id', $qr->jadwal_id)
            ->select('m.nama_mapel', 'j.jam_mulai', 'j.jam_selesai')
            ->first();

        kirimNotifikasiOrangTua(
            (int) $user->id,
            'Absensi Mapel',
            $user->nama.' sudah absen mapel '.($jadwal->nama_mapel ?? '-').' jam '.now()->format('H:i').'.',
            ['tipe' => 'mapel', 'mapel' => $jadwal->nama_mapel ?? '-', 'jam' => now()->format('H:i')]
        );

        return response()->json(['status' => 'success', 'message' => 'Absensi mapel berhasil']);
        });
    } catch (Exception $e) {
        Log::error('Scan mapel gagal', ['user_id' => optional($request->attributes->get('user_login'))->id, 'exception' => $e]);
        return response()->json(['status' => 'error', 'message' => 'Absensi mapel gagal diproses. Silakan coba kembali.'], 500);
    }
    }
}
