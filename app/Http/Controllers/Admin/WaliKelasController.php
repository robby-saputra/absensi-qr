<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaliKelasController extends Controller
{
    public function index()
    {
        $user = session('user');

        $wali = DB::table('kelas as k')
            ->leftJoin(
                'users as u',
                'u.id',
                '=',
                'k.wali_kelas_id'
            )
            ->leftJoin(
                'users as s',
                's.kelas_id',
                '=',
                'k.id'
            )
            ->select(
                'k.id',
                'k.nama_kelas',
                'k.wali_kelas_id',
                'u.nama',
                'u.username',
                DB::raw(
                    'COUNT(

                    CASE

                    WHEN

                    s.role="siswa"

                    AND

                    s.aktif=1

                    AND

                    s.deleted_at IS NULL

                    THEN

                    s.id

                    END

                )

                as

                jumlah_siswa'
                )
            )
            ->groupBy(
                'k.id',
                'k.nama_kelas',
                'k.wali_kelas_id',
                'u.nama',
                'u.username'
            )
            ->orderBy(
                'k.nama_kelas'
            )
            ->get();

        return view(
            'dashboard.wali_kelas.index',
            compact(
                'user',
                'wali'
            )
        );
    }

    public function create()
    {
        $user = session('user');

        $guru = User::where('role', 'guru')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get();

        $kelas = DB::table('kelas')
            ->orderBy('nama_kelas')
            ->get();

        return view('dashboard.wali_kelas.create', compact(
            'user',
            'guru',
            'kelas'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'guru_id' => 'required',
            'kelas_id' => 'required',
        ]);

        $cekGuru = DB::table('kelas')
            ->where(
                'wali_kelas_id',
                $request->guru_id
            )
            ->exists();

        if ($cekGuru) {
            return back()->with(
                'error',
                'Guru sudah menjadi wali kelas di kelas lain'
            );
        }

        if ($pesanGuruNonaktif = validasiGuruAktifIds([$request->guru_id])) {
            return back()
                ->withInput()
                ->with('error', $pesanGuruNonaktif);
        }

        $cekKelas = DB::table('kelas')
            ->where(
                'id',
                $request->kelas_id
            )
            ->whereNotNull(
                'wali_kelas_id'
            )
            ->exists();

        if ($cekKelas) {
            return back()->with(
                'error',
                'Kelas ini sudah mempunyai wali kelas'
            );
        }

        DB::table('kelas')
            ->where(
                'id',
                $request->kelas_id
            )
            ->update([
                'wali_kelas_id' => $request->guru_id,
                'updated_at' => now(),
            ]);

        return redirect('/dashboard/admin/wali-kelas')
            ->with(
                'success',
                'Wali kelas berhasil ditambahkan'
            );
    }

    public function edit($id)
    {
        $user = session('user');

        $kelas = DB::table('kelas')
            ->where('id', $id)
            ->first();

        $guru = User::where('role', 'guru')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->orderBy('nama')
            ->get();

        return view(
            'dashboard.wali_kelas.edit',
            compact(
                'user',
                'kelas',
                'guru'
            )
        );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'wali_kelas_id' => 'required',
        ]);

        $cek = DB::table('kelas')
            ->where(
                'wali_kelas_id',
                $request->wali_kelas_id
            )
            ->where(
                'id',
                '!=',
                $id
            )
            ->exists();

        if ($cek) {
            return back()
                ->with(
                    'error',
                    'Guru sudah menjadi wali kelas lain'
                );
        }

        if ($pesanGuruNonaktif = validasiGuruAktifIds([$request->wali_kelas_id])) {
            return back()
                ->withInput()
                ->with('error', $pesanGuruNonaktif);
        }

        DB::table('kelas')
            ->where(
                'id',
                $id
            )
            ->update([
                'wali_kelas_id' => $request->wali_kelas_id,
                'updated_at' => now(),
            ]);

        return redirect(
            '/dashboard/admin/wali-kelas'
        )
            ->with(
                'success',
                'Wali kelas berhasil diupdate'
            );
    }

    public function delete($id)
    {
        DB::table('kelas')
            ->where(
                'id',
                $id
            )
            ->update([
                'wali_kelas_id' => null,
                'updated_at' => now(),
            ]);

        return redirect(
            '/dashboard/admin/wali-kelas'
        )
            ->with(
                'success',
                'Wali kelas berhasil dihapus'
            );
    }
}

