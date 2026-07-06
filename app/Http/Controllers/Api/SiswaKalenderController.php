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
        $request->validate([
            'bulan' => 'nullable|integer|between:1,12',
            'tahun' => 'nullable|integer|between:2000,2100',
        ]);
        $authenticatedUser = $request->attributes->get('user_login');
        if (! $authenticatedUser || (int) $authenticatedUser->id !== (int) $siswa_id) {
            return response()->json(['status' => 'error', 'message' => 'Akses data siswa ditolak'], 403);
        }

        $user = User::where('role', 'siswa')->find($siswa_id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
        }

        $bulan = (int) ($request->query('bulan') ?: now()->month);
        $tahun = (int) ($request->query('tahun') ?: now()->year);
        $start = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $events = apiKalenderSiswa($start->toDateString(), $end->toDateString())
            ->filter(fn ($event) => in_array(strtolower((string) ($event['jenis'] ?? '')), ['libur', 'ujian', 'kegiatan'], true))
            ->filter(fn ($event) => ($event['tanggal_mulai'] ?? '') <= $end->toDateString()
                && ($event['tanggal_selesai'] ?? '') >= $start->toDateString())
            ->values();

        return response()->json([
            'status' => 'success',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'events' => $events,
        ]);
    }
}
