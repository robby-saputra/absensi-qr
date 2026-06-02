<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuruDashboardController extends Controller
{
    public function jadwal(Request $request)
    {
        return redirect('/dashboard/guru?page=jadwal');
    }

    public function verifikasiAbsensi(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'verifikasi'])));
    }

    public function riwayatAbsensi(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'riwayat'])));
    }

    public function rekapSiswa(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_siswa'])));
    }

    public function rekapAbsensi(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_absensi'])));
    }

    public function sesiDigantikan(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'sesi_digantikan'])));
    }

    public function rekapJadwal(Request $request)
    {
        return redirect('/dashboard/guru?'.http_build_query(array_merge($request->query(), ['page' => 'rekap_jadwal'])));
    }

    public function piketRekapJadwal(Request $request)
    {
        return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'jadwal'])));
    }
}
