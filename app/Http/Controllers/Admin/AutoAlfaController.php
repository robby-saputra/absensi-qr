<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Controller ini menjalankan proses otomatis untuk menandai siswa alfa pada tanggal tertentu.
class AutoAlfaController extends Controller
{
    // Memvalidasi tanggal lalu memanggil helper auto alfa harian.
    public function store(Request $request)
    {
        $request->validate(['tanggal' => 'required|date']);
        $result = jalankanAutoAlfaHarian($request->tanggal);

        return redirect()
            ->route('rekap.absensi', [
                'mode' => $request->get('mode', 'tanggal'),
                'tanggal' => $request->tanggal,
                'bulan' => $request->get('bulan', now()->format('Y-m')),
                'tahun_ajaran_id' => $request->get('tahun_ajaran_id'),
            ])
            ->with('success', 'Auto alfa selesai. Data dibuat: '.$result['created'].($result['skipped'] ? ' (skip: '.$result['skipped'].')' : ''));
    }
}

