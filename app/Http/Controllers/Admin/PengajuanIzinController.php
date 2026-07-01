<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengajuanIzinController extends Controller
{
    public function index(Request $request)
    {
        // Halaman ini dipakai admin untuk melihat seluruh pengajuan izin/sakit siswa.
        $user = session('user');

        // Filter membuat admin bisa mencari pengajuan berdasarkan nama, status, jenis, kelas, atau bulan.
        $filters = [
            'search' => trim((string) $request->get('search', '')),
            'status' => $request->get('status'),
            'jenis' => $request->get('jenis'),
            'kelas_id' => $request->get('kelas_id'),
            'bulan' => $request->get('bulan'),
        ];

        // Query menggabungkan data pengajuan, siswa, kelas, jurusan, dan reviewer.
        $query = DB::table('student_permit_requests as p')
            ->join('users as s', 's.id', '=', 'p.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->leftJoin('users as r', 'r.id', '=', 'p.reviewed_by')
            ->whereNull('p.deleted_at')
            ->select('p.*', 's.nama as nama_siswa', 's.nis', 'k.nama_kelas', 'j.nama_jurusan', 'j.kode_jurusan', 'r.nama as reviewer')
            ->when($filters['search'], fn ($query, $search) => $query->where(function ($where) use ($search) {
                $where->where('s.nama', 'like', '%'.$search.'%')->orWhere('s.nis', 'like', '%'.$search.'%')
                    ->orWhere('p.alasan', 'like', '%'.$search.'%');
            }))
            ->when($filters['status'], fn ($query, $status) => $query->where('p.status', $status))
            ->when($filters['jenis'], fn ($query, $jenis) => $query->where('p.jenis', $jenis))
            ->when($filters['kelas_id'], fn ($query, $kelasId) => $query->where('s.kelas_id', $kelasId))
            ->when($filters['bulan'], fn ($query, $bulan) => $query->whereYear('p.tanggal_mulai', substr($bulan, 0, 4))->whereMonth('p.tanggal_mulai', substr($bulan, 5, 2)));

        // Pengajuan menunggu ditaruh paling atas agar lebih mudah ditindaklanjuti admin.
        $pengajuan = $query->orderByRaw("CASE WHEN p.status = 'menunggu' THEN 0 ELSE 1 END")
            ->orderByDesc('p.created_at')->get();

        // Data kelas dipakai untuk pilihan filter di halaman pengajuan.
        $kelas = tanpaArsip(DB::table('kelas'), 'kelas')->orderBy('nama_kelas')->get();

        // Ringkasan dipakai untuk kartu statistik di dashboard pengajuan izin.
        $ringkasan = [
            'total' => $pengajuan->count(),
            'menunggu' => $pengajuan->where('status', 'menunggu')->count(),
            'disetujui' => $pengajuan->where('status', 'disetujui')->count(),
            'ditolak' => $pengajuan->where('status', 'ditolak')->count(),
            'berlangsung' => $pengajuan->filter(fn ($row) => $row->status === 'disetujui' && $row->tanggal_mulai <= now()->toDateString() && $row->tanggal_selesai >= now()->toDateString())->count(),
        ];

        return view('dashboard.pengajuan_izin', compact('user', 'pengajuan', 'kelas', 'filters', 'ringkasan'));
    }

    public function review(Request $request, $id)
    {
        // Admin hanya boleh memberi keputusan disetujui atau ditolak.
        $request->validate(['status' => 'required|in:disetujui,ditolak', 'catatan_review' => 'nullable|string']);

        // Helper prosesReviewPengajuanSiswa mengurus efek review ke absensi harian/mapel dan notifikasi guru.
        $result = prosesReviewPengajuanSiswa((int) $id, $request->status, $request->catatan_review, $request);

        return back()->with('success', 'Pengajuan berhasil direview. Absensi harian: '.$result['harian'].', absensi mapel: '.$result['mapel'].', guru diberi notifikasi: '.$result['guru_notified'].'.');
    }
}
