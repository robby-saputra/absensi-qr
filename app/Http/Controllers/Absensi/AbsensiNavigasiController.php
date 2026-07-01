<?php

namespace App\Http\Controllers\Absensi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Controller ini hanya mengarahkan menu absensi ke tab dashboard yang sesuai.
class AbsensiNavigasiController extends Controller
{
    // Mengarahkan menu absensi harian piket ke tab absensi pada dashboard piket.
    public function piketAbsensiHarian(Request $request)
    {
        return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'absensi'])));
    }

    // Mengarahkan menu riwayat absensi piket ke tab riwayat pada dashboard piket.
    public function piketRiwayatAbsensi(Request $request)
    {
        return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'riwayat'])));
    }

    // Mengarahkan menu rekap absensi mapel guru ke tab rekap absensi mapel.
    public function guruRekapAbsensiMapel(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_absensi_mapel'])));
    }
}
