<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Services\AttendanceSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QrController extends Controller
{
    /**
     * Generate QR untuk absensi
     */
    public function generate(Request $request)
    {
        $user = $request->attributes->get('user_login');

        // validasi tipe absensi
        $request->validate([
            'tipe' => 'required|in:masuk,pulang',
        ]);

        if (function_exists('hariLiburSekolah') && ($libur = hariLiburSekolah(now()->toDateString()))) {
            return response()->json([
                'status' => 'error',
                'code' => 'hari_libur',
                'message' => 'Hari ini libur: '.$libur->judul.'. QR absensi harian tidak bisa dibuat.',
            ], 422);
        }

        $hariSekarang = strtolower(now()->locale('id')->translatedFormat('l'));

        $teamBase = DB::table('guru_pikets')
            ->where('hari', $hariSekarang)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->when(($user->role ?? null) === 'guru', function ($query) use ($user) {
                $query->where('guru_id', $user->id);
            })
            ->orderBy('jam_mulai')
            ->first();

        if (! $teamBase) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tim guru piket hari ini belum ditemukan. QR tim tidak bisa dibuat.',
            ], 403);
        }

        $teamKey = implode('|', [
            $teamBase->tahun_ajaran_id ?? 'aktif',
            strtolower((string) $teamBase->hari),
            $teamBase->jam_mulai ?: '-',
            $teamBase->jam_selesai ?: '-',
        ]);

        $anggotaTim = DB::table('guru_pikets')
            ->where('hari', $teamBase->hari)
            ->where('jam_mulai', $teamBase->jam_mulai)
            ->where('jam_selesai', $teamBase->jam_selesai)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->when($teamBase->tahun_ajaran_id ?? null, fn ($query) => $query->where('tahun_ajaran_id', $teamBase->tahun_ajaran_id))
            ->orderBy('id')
            ->get();

        // cek apakah sudah ada QR tim hari ini dengan tipe yang sama
        $existing = QrCode::whereDate('tanggal', now()->toDateString())
            ->where('tipe', $request->tipe)
            ->when(Schema::hasColumn('qr_codes', 'guru_piket_team_key'), fn ($query) => $query->where('guru_piket_team_key', $teamKey))
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'success',
                'message' => 'QR tim untuk hari ini sudah tersedia',
                'data' => $existing,
                'team' => [
                    'key' => $teamKey,
                    'hari' => $teamBase->hari,
                    'jam_mulai' => $teamBase->jam_mulai,
                    'jam_selesai' => $teamBase->jam_selesai,
                    'jumlah_guru' => $anggotaTim->count(),
                ],
            ]);
        }

        $payload = [
            'tanggal' => now()->toDateString(),
            'tipe' => $request->tipe,
            'token' => Str::random(12),
            'expires_at' => now()->addMinutes(AttendanceSettingService::masaAktifQr()),
        ];

        if (Schema::hasColumn('qr_codes', 'generated_by')) {
            $payload['generated_by'] = $user->id ?? null;
        }

        if (Schema::hasColumn('qr_codes', 'guru_piket_team_key')) {
            $payload['guru_piket_team_key'] = $teamKey;
        }

        if (Schema::hasColumn('qr_codes', 'guru_piket_ids')) {
            $payload['guru_piket_ids'] = $anggotaTim->pluck('id')->implode(',');
        }

        // buat QR baru
        $qr = QrCode::create($payload);

        return response()->json([
            'status' => 'success',
            'message' => 'QR tim berhasil dibuat',
            'data' => $qr,
            'team' => [
                'key' => $teamKey,
                'hari' => $teamBase->hari,
                'jam_mulai' => $teamBase->jam_mulai,
                'jam_selesai' => $teamBase->jam_selesai,
                'jumlah_guru' => $anggotaTim->count(),
            ],
        ]);
    }
}
