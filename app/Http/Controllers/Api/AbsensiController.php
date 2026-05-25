<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\QrCode;
use App\Services\AttendanceSettingService;
use App\Services\WaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AbsensiController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        // AMBIL USER DARI MIDDLEWARE
        $user = $request->attributes->get('user_login');

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User login tidak ditemukan',
            ], 401);
        }

        // CEK QR
        $qr = QrCode::where('token', $request->token)->first();

        if (! $qr) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR tidak valid',
            ], 404);
        }

        if ($qr->tanggal != now()->toDateString()) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR sudah tidak berlaku',
            ], 403);
        }

        if (! $qr->tipe) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipe QR kosong',
            ], 400);
        }

        //  CEK ABSENSI HARI INI
        $absensi = Absensi::where('id_siswa', $user->id)
            ->whereDate('tanggal', now()->toDateString())
            ->first();

        $user->loadMissing('kelasRelasi');
        $namaKelas = $user->kelas ?? '-';

        //  FORMAT NOMOR WA (08 → 62)
        $ortu = $user->no_ortu;

        if ($ortu && substr($ortu, 0, 1) === '0') {
            $ortu = '62'.substr($ortu, 1);
        }

        $data = null;

        // =========================
        // ABSEN MASUK
        // =========================
        if (strtolower($qr->tipe) === 'masuk') {

            if ($absensi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda sudah absen masuk hari ini',
                ], 409);
            }

            $data = Absensi::create([
                'id_siswa' => $user->id,
                'tanggal' => now()->toDateString(),
                'jam_masuk' => now()->format('H:i:s'),
                'status_masuk' => now()->format('H:i:s') > AttendanceSettingService::jamMasuk() ? 'telat' : 'hadir',
            ]);

            // 📱 WA MASUK
            $message = "📢 ABSENSI MASUK\n\n"
                ."👤 Nama: {$user->nama}\n"
                ."🏫 Kelas: {$namaKelas}\n"
                .'⏰ Jam: '.now()->format('H:i:s')."\n"
                ."📌 Status: {$data->status_masuk}";

            $response = WaService::send($ortu, $message);

            if (! $response || ! $response->successful()) {
                Log::error('WA MASUK GAGAL', [
                    'response' => $response?->body(),
                    'ortu' => $ortu,
                    'nama' => $user->nama,
                ]);
            }
        }

        // =========================
        // ABSEN PULANG
        // =========================
        else {

            if (! $absensi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Belum absen masuk',
                ], 409);
            }

            if ($absensi->jam_pulang) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda sudah absen pulang',
                ], 409);
            }

            $absensi->update([
                'jam_pulang' => now()->format('H:i:s'),
                'status_pulang' => now()->format('H:i:s') < AttendanceSettingService::jamPulang() ? 'pulang_cepat' : 'pulang',
            ]);

            $data = $absensi;

            // 📱 WA PULANG
            $message = "📢 ABSENSI PULANG\n\n"
                ."👤 Nama: {$user->nama}\n"
                ."🏫 Kelas: {$namaKelas}\n"
                .'⏰ Jam: '.now()->format('H:i:s')."\n"
                .'📌 Status: Pulang';

            $response = WaService::send($ortu, $message);

            if (! $response || ! $response->successful()) {
                Log::error('WA PULANG GAGAL', [
                    'response' => $response?->body(),
                    'ortu' => $ortu,
                    'nama' => $user->nama,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi berhasil',
            'data' => $data,
        ]);
    }
}
