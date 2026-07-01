<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controller ini mengelola periode tahun ajaran dan semester sekolah.
class TahunAjaranController extends Controller
{
    // Menampilkan daftar tahun ajaran dengan yang aktif diletakkan di atas.
    public function index()
    {
        $user = session('user');
        $tahunAjaran = DB::table('tahun_ajarans')
            ->orderByDesc('aktif')
            ->orderByDesc('tanggal_mulai')
            ->get();

        return view('dashboard.tahun_ajaran.index', compact('user', 'tahunAjaran'));
    }

    // Menampilkan form tambah tahun ajaran.
    public function create()
    {
        $user = session('user');

        return view('dashboard.tahun_ajaran.create', compact('user'));
    }

    // Menyimpan tahun ajaran baru setelah memastikan tidak duplikat dan tidak bentrok tanggal.
    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'semester' => 'required|in:ganjil,genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        // Mencegah nama tahun ajaran dan semester yang sama dibuat dua kali.
        $exists = DB::table('tahun_ajarans')
            ->where('nama', $request->nama)
            ->where('semester', $request->semester)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Tahun ajaran dan semester tersebut sudah ada.');
        }

        // Rentang tanggal tidak boleh bertabrakan dengan periode tahun ajaran lain.
        $overlap = DB::table('tahun_ajarans')
            ->whereDate('tanggal_mulai', '<=', $request->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $request->tanggal_mulai)
            ->exists();

        if ($overlap) {
            return back()
                ->withInput()
                ->with('error', 'Rentang tanggal bentrok dengan tahun ajaran lain.');
        }

        if ($request->has('aktif')) {
            DB::table('tahun_ajarans')->update(['aktif' => false, 'updated_at' => now()]);
        }

        DB::table('tahun_ajarans')->insert([
            'nama' => $request->nama,
            'semester' => $request->semester,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'aktif' => $request->has('aktif'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/dashboard/admin/tahun-ajaran')->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = session('user');
        $tahun = DB::table('tahun_ajarans')->where('id', $id)->first();

        abort_if(! $tahun, 404);

        return view('dashboard.tahun_ajaran.edit', compact('user', 'tahun'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nama' => 'required',
            'semester' => 'required|in:ganjil,genap',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $exists = DB::table('tahun_ajarans')
            ->where('nama', $request->nama)
            ->where('semester', $request->semester)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Tahun ajaran dan semester tersebut sudah ada.');
        }

        $overlap = DB::table('tahun_ajarans')
            ->where('id', '!=', $id)
            ->whereDate('tanggal_mulai', '<=', $request->tanggal_selesai)
            ->whereDate('tanggal_selesai', '>=', $request->tanggal_mulai)
            ->exists();

        if ($overlap) {
            return back()
                ->withInput()
                ->with('error', 'Rentang tanggal bentrok dengan tahun ajaran lain.');
        }

        if ($request->has('aktif')) {
            DB::table('tahun_ajarans')->where('id', '!=', $id)->update(['aktif' => false, 'updated_at' => now()]);
        }

        $before = DB::table('tahun_ajarans')->where('id', $id)->first();

        DB::table('tahun_ajarans')
            ->where('id', $id)
            ->update([
                'nama' => $request->nama,
                'semester' => $request->semester,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'aktif' => $request->has('aktif'),
                'updated_at' => now(),
            ]);

        return redirect('/dashboard/admin/tahun-ajaran')->with('success', 'Tahun ajaran berhasil diupdate.');
    }

    public function aktif($id)
    {
        abort_if(! DB::table('tahun_ajarans')->where('id', $id)->exists(), 404);

        DB::table('tahun_ajarans')->update(['aktif' => false, 'updated_at' => now()]);
        DB::table('tahun_ajarans')->where('id', $id)->update(['aktif' => true, 'updated_at' => now()]);

        return back()->with('success', 'Tahun ajaran aktif berhasil diganti.');
    }

    public function delete($id)
    {
        $tahun = DB::table('tahun_ajarans')->where('id', $id)->first();

        abort_if(! $tahun, 404);

        if ($tahun->aktif) {
            return back()->with('error', 'Tahun ajaran aktif tidak bisa dihapus.');
        }

        if (! hapusDataAdmin('tahun_ajarans', (int) $id, 'Tahun ajaran', request())) {
            return back()->with('error', 'Tahun ajaran gagal dihapus atau data tidak ditemukan.');
        }

        return back()->with('success', 'Tahun ajaran berhasil dipindahkan ke arsip.');
    }
}

