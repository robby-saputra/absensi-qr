<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiswaPengajuanIzinController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->attributes->get('user_login');
        if (! $user || $user->role !== 'siswa' || ! $user->aktif) {
            return response()->json(['status' => 'error', 'message' => 'Akun siswa tidak aktif'], 403);
        }

        $request->validate([
            'siswa_id' => 'nullable|exists:users,id',
            'jenis' => 'required|in:izin,sakit',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        if ($request->filled('siswa_id') && (int) $request->siswa_id !== (int) $user->id) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak sesuai dengan token akses'], 403);
        }

        $path = null;
        if ($request->hasFile('bukti')) {
            $path = $request->file('bukti')->store('bukti-izin', 'public');
        }

        $id = DB::table('student_permit_requests')->insertGetId([
            'siswa_id' => $user->id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jenis' => $request->jenis,
            'alasan' => $request->alasan,
            'bukti_path' => $path,
            'status' => 'menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $siswa = $user;

        if (function_exists('apiBuatNotifikasiAdmin')) {
            apiBuatNotifikasiAdmin(
                'pengajuan_izin',
                'Pengajuan '.ucfirst($request->jenis).' Baru',
                ($siswa->nama ?? 'Siswa').' mengajukan '.$request->jenis.' dari '.$request->tanggal_mulai.' sampai '.$request->tanggal_selesai.'.',
                [
                    'source_type' => 'student_permit_requests',
                    'source_id' => $id,
                    'siswa_id' => $user->id,
                    'siswa' => $siswa->nama ?? null,
                    'jenis' => $request->jenis,
                    'tanggal_mulai' => $request->tanggal_mulai,
                    'tanggal_selesai' => $request->tanggal_selesai,
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pengajuan '.$request->jenis.' berhasil dikirim dan menunggu verifikasi.',
            'id' => $id,
        ]);
    }
}
