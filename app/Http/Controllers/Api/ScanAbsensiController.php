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

class ScanAbsensiController extends Controller
{
    public function store(Request $request)
    {

    $user = User::find($request->user_id);

    if (! $user) {

        return response()->json([
            'status' => 'error',
            'message' => 'User tidak ditemukan',
        ]);
    }

    if ($lokasiError = apiValidasiLokasiSekolah($request)) {
        return $lokasiError;
    }

    $qr = QrCode::where('token', $request->token)
        ->first();

    if (! $qr) {

        return response()->json([
            'status' => 'error',
            'message' => 'QR tidak valid',
        ]);
    }

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

    if ($qr->expires_at && now()->greaterThan($qr->expires_at)) {
        return response()->json([
            'status' => 'error',
            'message' => 'QR sudah kedaluwarsa',
        ]);
    }

    $cek = Absensi::where('id_siswa', $user->id)
        ->whereDate('tanggal', now()->toDateString())
        ->first();

    /*
    |--------------------------------------------------------------------------
    | ABSEN MASUK
    |--------------------------------------------------------------------------
    */
    if ($qr->tipe == 'masuk') {

        if ($cek && $cek->jam_masuk) {

            return response()->json([
                'status' => 'error',
                'message' => 'Sudah absen masuk',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CEK STATUS TELAT
        |--------------------------------------------------------------------------
        */
        $jamSekarang = now()->format('H:i:s');

        $batasMasuk = AttendanceSettingService::batasTelat();

        $statusMasuk = 'hadir';

        if ($jamSekarang > $batasMasuk) {

            $statusMasuk = 'telat';
        }

        $tahunAjaranId = DB::table('tahun_ajarans')->where('aktif', true)->value('id');

        if (! $cek) {

            Absensi::create([

                'id_siswa' => $user->id,

                'tahun_ajaran_id' => $tahunAjaranId,

                'tanggal' => now()->toDateString(),

                'jam_masuk' => $jamSekarang,

                'status_masuk' => $statusMasuk,
            ]);

        } else {

            $cek->update([

                'tahun_ajaran_id' => $cek->tahun_ajaran_id ?: $tahunAjaranId,

                'jam_masuk' => $jamSekarang,

                'status_masuk' => $statusMasuk,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ABSEN PULANG
    |--------------------------------------------------------------------------
    */
    if ($qr->tipe == 'pulang') {

        if (! $cek) {

            return response()->json([
                'status' => 'error',
                'message' => 'Belum absen masuk',
            ]);
        }

        if ($cek->jam_pulang) {

            return response()->json([
                'status' => 'error',
                'message' => 'Sudah absen pulang',
            ]);
        }

        $cek->update([

            'tahun_ajaran_id' => $cek->tahun_ajaran_id ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id'),

            'jam_pulang' => now()->format('H:i:s'),

            'status_pulang' => now()->format('H:i:s') < AttendanceSettingService::jamPulang() ? 'pulang_cepat' : 'pulang',
        ]);
    }

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

        kirimNotifikasiOrangTua(
            (int) $user->id,
            'Absensi Masuk',
            $user->nama.' sudah absen masuk jam '.now()->format('H:i').' dengan status '.($statusMasuk ?? 'hadir').'.',
            ['tipe' => 'masuk', 'jam' => now()->format('H:i'), 'status' => $statusMasuk ?? 'hadir']
        );
    }

    if ($qr->tipe === 'pulang') {
        kirimNotifikasiOrangTua(
            (int) $user->id,
            'Absensi Pulang',
            $user->nama.' sudah absen pulang jam '.now()->format('H:i').'.',
            ['tipe' => 'pulang', 'jam' => now()->format('H:i')]
        );
    }

    return response()->json([

        'status' => 'success',

        'message' => 'Absensi berhasil',
    ]);
    }
}
