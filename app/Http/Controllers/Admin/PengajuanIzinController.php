<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengajuanIzinController extends Controller
{
    public function index()
    {
        $user = session('user');
        $pengajuan = DB::table('student_permit_requests as p')
            ->join('users as s', 's.id', '=', 'p.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')
            ->whereNull('p.deleted_at')
            ->select('p.*', 's.nama as nama_siswa', 'k.nama_kelas', 'r.nama as reviewer')
            ->latest('p.id')
            ->get();

        return view('dashboard.pengajuan_izin', compact('user', 'pengajuan'));
    }

    public function review(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:disetujui,ditolak', 'catatan_review' => 'nullable|string']);
        $result = prosesReviewPengajuanSiswa((int) $id, $request->status, $request->catatan_review, $request);

        return back()->with('success', 'Pengajuan berhasil direview. Absensi harian: '.$result['harian'].', absensi mapel: '.$result['mapel'].', guru diberi notifikasi: '.$result['guru_notified'].'.');
    }
}

