<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SiswaDashboardController extends Controller
{
    public function index()
    {
        $user = session('user');
        $tanggal = now()->toDateString();
        $absensiHariIni = DB::table('absensis')->where('id_siswa', $user->id)->whereDate('tanggal', $tanggal)->whereNull('deleted_at')->first();
        $riwayatHarian = DB::table('absensis')->where('id_siswa', $user->id)->whereNull('deleted_at')->orderByDesc('tanggal')->limit(30)->get();
        $riwayatMapel = DB::table('absensi_mapels as a')
            ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->where('a.siswa_id', $user->id)
            ->whereNull('a.deleted_at')
            ->select('a.*', 'm.nama_mapel', 'g.nama as nama_guru', 'j.jam_mulai', 'j.jam_selesai')
            ->orderByDesc('a.tanggal')
            ->limit(40)
            ->get();
        $pengajuan = DB::table('student_permit_requests')->where('siswa_id', $user->id)->whereNull('deleted_at')->latest('id')->get();
        $kelas = DB::table('kelas as k')->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')->where('k.id', $user->kelas_id)->select('k.*', 'j.nama_jurusan')->first();
        $infoLiburHariIni = infoLiburHariIni('siswa');

        return view('dashboard.users', compact('user', 'tanggal', 'absensiHariIni', 'riwayatHarian', 'riwayatMapel', 'pengajuan', 'kelas', 'infoLiburHariIni'));
    }

    public function storeIzin(Request $request)
    {
        $user = session('user');
        $request->validate([
            'jenis' => 'required|in:izin,sakit',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $path = $request->hasFile('bukti') ? $request->file('bukti')->store('bukti-izin', 'public') : null;
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
        AuditLogger::record('create', 'student_permit_requests', (int) $id, 'Pengajuan izin/sakit siswa dibuat', null, DB::table('student_permit_requests')->where('id', $id)->first(), $request);

        if (function_exists('buatNotifikasi')) {
            buatNotifikasi([
                'user_id' => null,
                'judul' => 'Pengajuan '.ucfirst($request->jenis).' Baru',
                'pesan' => $user->nama.' mengajukan '.$request->jenis.' dari '.$request->tanggal_mulai.' sampai '.$request->tanggal_selesai.'.',
                'status' => 'belum_dibaca',
                'kategori' => 'pengajuan_izin',
                'severity' => 'info',
                'source_type' => 'student_permit_requests',
                'source_id' => $id,
                'payload' => [
                    'siswa_id' => $user->id,
                    'siswa' => $user->nama,
                    'jenis' => $request->jenis,
                    'tanggal_mulai' => $request->tanggal_mulai,
                    'tanggal_selesai' => $request->tanggal_selesai,
                ],
            ]);
        }

        return back()->with('success', 'Pengajuan berhasil dikirim dan menunggu verifikasi.');
    }
}
