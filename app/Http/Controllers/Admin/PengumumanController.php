<?php

namespace App\Http\Controllers\Admin;

use App\Support\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengumumanController extends Controller
{
    public function index()
    {
        wajibSuperadmin();
        $user = session('user');
        $pengumuman = DB::table('announcements as a')->leftJoin('users as u', 'u.id', '=', 'a.created_by')->whereNull('a.deleted_at')->select('a.*', 'u.nama as pembuat')->latest('a.id')->get();

        return view('dashboard.pengumuman.index', compact('user', 'pengumuman'));
    }

    public function create()
    {
        wajibSuperadmin();
        $user = session('user');
        $item = null;
        $mode = 'create';

        return view('dashboard.pengumuman.form', compact('user', 'item', 'mode'));
    }

    public function edit($id)
    {
        wajibSuperadmin();
        $user = session('user');
        $item = DB::table('announcements')->where('id', $id)->first();
        abort_if(! $item, 404);
        $mode = 'edit';

        return view('dashboard.pengumuman.form', compact('user', 'item', 'mode'));
    }

    public function store(Request $request)
    {
        wajibSuperadmin();
        $request->validate([
            'judul' => 'required|max:255',
            'isi' => 'required',
            'target_role' => 'required|in:semua,guru,piket,wali',
            'kategori' => 'required|in:info,libur,ujian,jadwal,piket',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);
        $id = DB::table('announcements')->insertGetId([
            'created_by' => session('user')->id,
            'judul' => $request->judul,
            'isi' => $request->isi,
            'target_role' => $request->target_role,
            'kategori' => $request->kategori,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'aktif' => $request->has('aktif'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        AuditLogger::record('create', 'announcements', (int) $id, 'Pengumuman dibuat', null, DB::table('announcements')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/pengumuman')->with('success', 'Pengumuman berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        wajibSuperadmin();
        $request->validate([
            'judul' => 'required|max:255',
            'isi' => 'required',
            'target_role' => 'required|in:semua,guru,piket,wali',
            'kategori' => 'required|in:info,libur,ujian,jadwal,piket',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
        ]);
        $before = DB::table('announcements')->where('id', $id)->first();
        abort_if(! $before, 404);
        DB::table('announcements')->where('id', $id)->update([
            'judul' => $request->judul,
            'isi' => $request->isi,
            'target_role' => $request->target_role,
            'kategori' => $request->kategori,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'aktif' => $request->has('aktif'),
            'updated_at' => now(),
        ]);
        AuditLogger::record('update', 'announcements', (int) $id, 'Pengumuman diupdate', $before, DB::table('announcements')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/pengumuman')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function delete($id)
    {
        wajibSuperadmin();
        if (! arsipkanData('announcements', (int) $id, 'Pengumuman', request())) {
            return back()->with('error', 'Pengumuman gagal diarsipkan atau data tidak ditemukan.');
        }

        return back()->with('success', 'Pengumuman berhasil diarsipkan.');
    }
}

