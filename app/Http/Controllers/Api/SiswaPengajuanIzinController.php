<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiswaPengajuanIzinController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:users,id',
            'jenis' => 'required|in:izin,sakit',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ]);

        $path = null;
        if ($request->hasFile('bukti')) {
            $path = $request->file('bukti')->store('bukti-izin', 'public');
        }

        $id = DB::table('student_permit_requests')->insertGetId([
            'siswa_id' => $request->siswa_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jenis' => $request->jenis,
            'alasan' => $request->alasan,
            'bukti_path' => $path,
            'status' => 'menunggu',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $siswa = DB::table('users')->where('id', $request->siswa_id)->first();

        if (function_exists('apiBuatNotifikasiAdmin')) {
            apiBuatNotifikasiAdmin(
                'pengajuan_izin',
                'Pengajuan '.ucfirst($request->jenis).' Baru',
                ($siswa->nama ?? 'Siswa').' mengajukan '.$request->jenis.' dari '.$request->tanggal_mulai.' sampai '.$request->tanggal_selesai.'.',
                [
                    'source_type' => 'student_permit_requests',
                    'source_id' => $id,
                    'siswa_id' => $request->siswa_id,
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
