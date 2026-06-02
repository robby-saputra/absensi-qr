<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WaliKelasDashboardController extends Controller
{
    public function siswa()
    {
        $user = session('user');

        $wali = DB::table('kelas')
            ->select('id', 'nama_kelas')
            ->where('wali_kelas_id', $user->id)
            ->first();

        if (! $wali) {
            abort(403);
        }

        $siswa = User::where('role', 'siswa')
            ->where('kelas_id', $wali->id)
            ->orderBy('nama')
            ->get();

        foreach ($siswa as $item) {
            $item->nama_kelas = $wali->nama_kelas;
        }

        return view('dashboard.wali_siswa', compact(
            'user',
            'wali',
            'siswa'
        ));
    }

    public function detailSiswa($id)
    {
        $user = session('user');

        $wali = DB::table('kelas')
            ->select('id', 'nama_kelas')
            ->where('wali_kelas_id', $user->id)
            ->first();

        if (! $wali) {
            abort(403);
        }

        $target = User::where('role', 'siswa')
            ->where('kelas_id', $wali->id)
            ->where('id', $id)
            ->first();

        if (! $target) {
            abort(403);
        }

        $data = detailProfilSiswaData((int) $id);
        $catatanWali = Schema::hasTable('wali_followups')
            ? DB::table('wali_followups')->where('siswa_id', $id)->where('wali_id', $user->id)->latest('tanggal')->limit(20)->get()
            : collect();
        $pengajuanSiswa = Schema::hasTable('student_permit_requests')
            ? DB::table('student_permit_requests')->where('siswa_id', $id)->whereNull('deleted_at')->latest('id')->limit(20)->get()
            : collect();
        $layout = 'wali';

        return view('dashboard.siswa.detail', $data + compact('user', 'layout', 'catatanWali', 'pengajuanSiswa'));
    }
}
