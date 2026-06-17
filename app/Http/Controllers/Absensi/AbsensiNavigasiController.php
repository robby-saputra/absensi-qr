<?php

namespace App\Http\Controllers\Absensi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AbsensiNavigasiController extends Controller
{
    public function piketAbsensiHarian(Request $request)
    {
        return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'absensi'])));
    }

    public function piketRiwayatAbsensi(Request $request)
    {
        return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'riwayat'])));
    }

    public function guruRekapAbsensiMapel(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_absensi_mapel'])));
    }
}
