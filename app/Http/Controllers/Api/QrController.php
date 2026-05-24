<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QrCode;
use Illuminate\Support\Str;

class QrController extends Controller
{
    /**
     * Generate QR untuk absensi
     */
    public function generate(Request $request)
    {
        // validasi tipe absensi
        $request->validate([
            'tipe' => 'required|in:masuk,pulang'
        ]);

        // cek apakah sudah ada QR hari ini dengan tipe yang sama
        $existing = QrCode::whereDate('tanggal', now()->toDateString())
            ->where('tipe', $request->tipe)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'QR untuk hari ini sudah dibuat'
            ]);
        }

        // buat QR baru
        $qr = QrCode::create([
            'tanggal' => now()->toDateString(),
            'tipe' => $request->tipe,
            'token' => Str::random(12),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'QR berhasil dibuat',
            'data' => $qr
        ]);
    }
}