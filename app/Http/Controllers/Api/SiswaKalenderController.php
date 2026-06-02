<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SiswaKalenderController extends Controller
{
    public function index(Request $request, $siswa_id)
    {
        $user = User::where('role', 'siswa')->find($siswa_id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
        }

        $bulan = (int) ($request->query('bulan') ?: now()->month);
        $tahun = (int) ($request->query('tahun') ?: now()->year);
        $start = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return response()->json([
            'status' => 'success',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'events' => apiKalenderSiswa($start->toDateString(), $end->toDateString()),
        ]);
    }
}
