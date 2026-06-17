<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
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

}
