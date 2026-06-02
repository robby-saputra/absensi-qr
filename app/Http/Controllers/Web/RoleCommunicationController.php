<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleCommunicationController extends Controller
{
    public function pesanIndex()
    {
        $user = session('user');
        $inbox = DB::table('internal_messages as m')
            ->join('users as s', 's.id', '=', 'm.sender_id')
            ->leftJoin('users as siswa', 'siswa.id', '=', 'm.siswa_id')
            ->where('m.receiver_id', $user->id)
            ->select('m.*', 's.nama as pengirim', 'siswa.nama as nama_siswa')
            ->latest('m.id')
            ->limit(80)
            ->get();
        $guru = User::where('role', 'guru')->where('id', '!=', $user->id)->orderBy('nama')->get();
        $siswa = User::where('role', 'siswa')->orderBy('nama')->limit(500)->get();

        DB::table('internal_messages')->where('receiver_id', $user->id)->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);

        return view('dashboard.role_messages', compact('user', 'inbox', 'guru', 'siswa'));
    }

    public function pesanStore(Request $request)
    {
        $user = session('user');
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'siswa_id' => 'nullable|integer|exists:users,id',
            'judul' => 'nullable|string|max:120',
            'pesan' => 'required|string|max:1500',
        ]);

        $id = DB::table('internal_messages')->insertGetId([
            'sender_id' => $user->id,
            'receiver_id' => $request->receiver_id,
            'siswa_id' => $request->siswa_id,
            'kategori' => 'catatan_siswa',
            'judul' => $request->judul ?: 'Pesan Internal',
            'pesan' => $request->pesan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        buatNotifikasi([
            'user_id' => (int) $request->receiver_id,
            'judul' => 'Pesan Internal Baru',
            'pesan' => $user->nama.' mengirim pesan: '.($request->judul ?: 'Pesan Internal'),
            'kategori' => 'pesan_internal',
            'severity' => 'info',
            'source_type' => 'internal_messages',
            'source_id' => $id,
        ]);

        return back()->with('success', 'Pesan berhasil dikirim.');
    }

    public function delegasiIndex()
    {
        $user = session('user');
        $delegasi = DB::table('temporary_delegations as d')
            ->join('users as to', 'to.id', '=', 'd.to_user_id')
            ->where('d.from_user_id', $user->id)
            ->select('d.*', 'to.nama as nama_pengganti')
            ->latest('d.id')
            ->get();
        $guru = User::where('role', 'guru')->where('id', '!=', $user->id)->orderBy('nama')->get();

        return view('dashboard.role_delegations', compact('user', 'delegasi', 'guru'));
    }

    public function delegasiStore(Request $request)
    {
        $user = session('user');
        $request->validate([
            'to_user_id' => 'required|integer|exists:users,id',
            'role_context' => 'required|in:guru_mapel,guru_piket,wali_kelas',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'nullable|string|max:1000',
        ]);

        $id = DB::table('temporary_delegations')->insertGetId([
            'from_user_id' => $user->id,
            'to_user_id' => $request->to_user_id,
            'role_context' => $request->role_context,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'alasan' => $request->alasan,
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLogger::record('create', 'temporary_delegations', (int) $id, 'Delegasi sementara dibuat', null, DB::table('temporary_delegations')->where('id', $id)->first(), $request);
        buatNotifikasi([
            'user_id' => (int) $request->to_user_id,
            'judul' => 'Delegasi Sementara',
            'pesan' => $user->nama.' menunjuk Anda sebagai pengganti sementara untuk '.str_replace('_', ' ', $request->role_context).'.',
            'kategori' => 'delegasi_sementara',
            'severity' => 'warning',
            'source_type' => 'temporary_delegations',
            'source_id' => $id,
        ]);

        return back()->with('success', 'Delegasi sementara berhasil dibuat.');
    }
}
