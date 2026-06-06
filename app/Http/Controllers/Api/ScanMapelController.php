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

class ScanMapelController extends Controller
{
    public function store(Request $request)
    {
    try {
        $user = User::find($request->user_id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan']);
        } if ($lokasiError = apiValidasiLokasiSekolah($request)) {
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
        } if (($qr->expires_at ?? null) && now()->greaterThan($qr->expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'QR Mapel sudah kedaluwarsa']);
        } /* |-------------------------------------------------------------------------- | CEK QR AKTIF |-------------------------------------------------------------------------- */ if ($qr->aktif != 1) {
            return response()->json(['status' => 'error', 'message' => 'QR sesi sudah ditutup']);
        }

        $absensiHarian = Absensi::where('id_siswa', $user->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        if (! $absensiHarian || ! $absensiHarian->jam_masuk) {
            return response()->json([
                'status' => 'error',
                'message' => 'Absensi harian belum tercatat. Silakan scan QR masuk terlebih dahulu.',
            ]);
        }

        /* |-------------------------------------------------------------------------- | CEK DOUBLE ABSEN |-------------------------------------------------------------------------- */ $cek = DB::table('absensi_mapels')->where('siswa_id', $user->id)->where('jadwal_id', $qr->jadwal_id)->whereDate('tanggal', now()->toDateString())->first();
        if ($cek) {
            return response()->json(['status' => 'error', 'message' => 'Sudah absen mapel ini']);
        }

        /* |--------------------------------------------------------------------------
        | SIMPAN ABSENSI MAPEL
        |-------------------------------------------------------------------------- */
        $jadwalTahunAjaranId = DB::table('jadwal_pelajarans')
            ->where('id', $qr->jadwal_id)
            ->value('tahun_ajaran_id') ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id');

        DB::table('absensi_mapels')->insert([
            'tahun_ajaran_id' => $jadwalTahunAjaranId,
            'jadwal_id' => $qr->jadwal_id,
            'siswa_id' => $user->id,
            'tanggal' => now()->toDateString(),
            'jam_scan' => now()->format('H:i:s'),
            'status' => 'hadir',
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
    } catch (Exception $e) {
        return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
    }
    }
}
