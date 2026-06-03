<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurusanController extends Controller
{
    public function index()
    {
        $user = session('user');

        $jurusan = tanpaArsip(DB::table('jurusan'), 'jurusan')
            ->latest('id')
            ->get();

        return view('dashboard.jurusan.index', compact(
            'user',
            'jurusan'
        ));
    }

    public function create()
    {
        $user = session('user');

        return view('dashboard.jurusan.create', compact(
            'user'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_jurusan' => 'required',
            'kode_jurusan' => 'required',
        ]);

        DB::table('jurusan')->insert([

            'nama_jurusan' => $request->nama_jurusan,
            'kode_jurusan' => $request->kode_jurusan,

            'created_at' => now(),
            'updated_at' => now(),

        ]);

        return redirect('/dashboard/admin/jurusan')
            ->with('success', 'Jurusan berhasil ditambahkan');
    }

    public function delete($id)
    {
        if (! arsipkanData('jurusan', (int) $id, 'Jurusan', request())) {
            return redirect('/dashboard/admin/jurusan')
                ->with('error', 'Jurusan gagal dihapus atau data tidak ditemukan.');
        }

        return redirect('/dashboard/admin/jurusan')
            ->with('success', 'Jurusan berhasil dihapus');
    }
}

