<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controller ini mengatur notifikasi sistem yang hanya boleh diubah superadmin.
class NotifikasiSettingController extends Controller
{
    // Menampilkan nilai pengaturan notifikasi dari tabel attendance_settings.
    public function index()
    {
        wajibSuperadmin();
        $user = session('user');
        $settings = DB::table('attendance_settings')
            ->whereIn('key', ['notif_pengajuan_izin_guru', 'notif_belum_absen_pulang', 'notif_absen_masuk_admin'])
            ->pluck('value', 'key');

        return view('dashboard.notifikasi_setting', compact('user', 'settings'));
    }

    // Menyimpan pengaturan notifikasi berdasarkan checkbox yang dikirim dari form.
    public function store(Request $request)
    {
        wajibSuperadmin();
        foreach ([
            'notif_pengajuan_izin_guru' => $request->has('notif_pengajuan_izin_guru') ? '1' : '0',
            'notif_belum_absen_pulang' => $request->has('notif_belum_absen_pulang') ? '1' : '0',
            'notif_absen_masuk_admin' => $request->has('notif_absen_masuk_admin') ? '1' : '0',
        ] as $key => $value) {
            DB::table('attendance_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
        }

        return back()->with('success', 'Pengaturan notifikasi berhasil disimpan.');
    }
}
