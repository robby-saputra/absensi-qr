<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuruPiket;
use App\Models\User;
use Illuminate\Http\Request;

class GuruPiketController extends Controller
{
    // tampil data
    public function index()
    {
        $guruPikets = GuruPiket::with('guru')->latest()->get();

        return view('admin.guru_piket.index', compact('guruPikets'));
    }

    // form tambah
    public function create()
    {
        $gurus = User::where('role', 'guru')->get();

        return view('admin.guru_piket.create', compact('gurus'));
    }

    // simpan data
    public function store(Request $request)
    {
        $request->validate([
            'guru_id' => 'required',
            'hari' => 'required'
        ]);

        GuruPiket::create([
            'guru_id' => $request->guru_id,
            'hari' => $request->hari
        ]);

        return redirect()->route('guru-piket.index')
            ->with('success', 'Guru piket berhasil ditambahkan');
    }

    // hapus
    public function destroy($id)
    {
        GuruPiket::findOrFail($id)->delete();

        return back()->with('success', 'Data berhasil dihapus');
    }
}