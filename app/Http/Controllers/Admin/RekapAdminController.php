<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RekapAdminController extends Controller
{
    public function guruPiket(Request $request)
    {
        $user = session('user');
        $hari = $request->get('hari');
        $status = $request->get('status');

        $query = DB::table('guru_pikets as gp')
            ->join('users as g', 'g.id', '=', 'gp.guru_id')
            ->select(
                'gp.*',
                'g.nama as guru_utama'
            );

        if ($hari) {
            $query->where('gp.hari', strtolower($hari));
        }

        if ($status) {
            $query->where('gp.status', $status);
        }

        $data = $query
            ->orderBy('gp.hari')
            ->orderBy('gp.jam_mulai')
            ->orderBy('g.nama')
            ->get();

        return view('dashboard.rekap.guru_piket', compact('user', 'data', 'hari', 'status'));
    }

    public function absensiMapel(Request $request)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal');
        $kelasId = $request->get('kelas_id');
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id');

        $query = DB::table('absensi_mapels as a')
            ->join('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->join('jadwal_pelajarans as j', 'j.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->select(
                'a.*',
                's.nama as nama_siswa',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as guru_utama',
                'j.status_guru',
                'j.alasan_tidak_hadir',
                'j.hari',
                'j.jam_mulai',
                'j.jam_selesai'
            );

        tanpaArsip($query, 'absensi_mapels', 'a');

        if ($tanggal) {
            $query->whereDate('a.tanggal', $tanggal);
        }

        if ($kelasId) {
            $query->where('s.kelas_id', $kelasId);
        }

        if ($tahunAjaranId && Schema::hasColumn('absensi_mapels', 'tahun_ajaran_id')) {
            $query->where('a.tahun_ajaran_id', $tahunAjaranId);
        }

        $data = $query
            ->latest('a.tanggal')
            ->orderBy('k.nama_kelas')
            ->orderBy('s.nama')
            ->get();

        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();

        return view('dashboard.rekap.absensi_mapel', compact('user', 'data', 'tanggal', 'kelasId', 'kelas', 'tahunAjaran', 'tahunAjaranId'));
    }

    public function jadwalGuruMapel(Request $request)
    {
        $user = session('user');
        $guruId = $request->get('guru_id');
        $hari = $request->get('hari');

        $query = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as guru_utama'
            );

        if ($guruId) {
            $query->where('j.guru_id', $guruId);
        }

        if ($hari) {
            $query->where('j.hari', $hari);
        }

        $data = $query
            ->orderBy('g.nama')
            ->orderBy('j.hari')
            ->orderBy('j.jam_mulai')
            ->get();

        $guru = User::where('role', 'guru')->orderBy('nama')->get();

        return view('dashboard.rekap.jadwal_guru_mapel', compact('user', 'data', 'guru', 'guruId', 'hari'));
    }
}
