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
        // Bulan dan tahun boleh dikirim dari aplikasi untuk melihat kalender bulan tertentu.
        $request->validate([
            'bulan' => 'nullable|integer|between:1,12',
            'tahun' => 'nullable|integer|between:2000,2100',
        ]);

        // Data user dari token harus sama dengan siswa yang kalendernya diminta.
        $authenticatedUser = $request->attributes->get('user_login');
        if (! $authenticatedUser || (int) $authenticatedUser->id !== (int) $siswa_id) {
            return response()->json(['status' => 'error', 'message' => 'Akses data siswa ditolak'], 403);
        }

        // Memastikan siswa benar-benar ada dan role-nya siswa.
        $user = User::where('role', 'siswa')->find($siswa_id);
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan'], 404);
        }

        // Jika bulan/tahun tidak dikirim, sistem memakai bulan dan tahun saat ini.
        $bulan = (int) ($request->query('bulan') ?: now()->month);
        $tahun = (int) ($request->query('tahun') ?: now()->year);
        $start = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Kalender siswa hanya menampilkan kategori penting untuk mobile:
        // libur, ujian, dan kegiatan.
        $events = apiKalenderSiswa($start->toDateString(), $end->toDateString())
            ->filter(fn ($event) => in_array(strtolower((string) ($event['jenis'] ?? '')), ['libur', 'ujian', 'kegiatan'], true))
            ->filter(fn ($event) => ($event['tanggal_mulai'] ?? '') <= $end->toDateString()
                && ($event['tanggal_selesai'] ?? '') >= $start->toDateString())
            ->values();

        // Response berisi bulan, tahun, dan daftar event kalender.
        return response()->json([
            'status' => 'success',
            'bulan' => $bulan,
            'tahun' => $tahun,
            'events' => $events,
        ]);
    }
}
