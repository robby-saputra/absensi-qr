<?php

namespace App\Http\Controllers\Admin;

use App\Support\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotifikasiSettingController extends Controller
{
    public function index()
    {
        wajibSuperadmin();
        $user = session('user');
        $settings = DB::table('attendance_settings')->whereIn('key', ['notif_login_mencurigakan', 'notif_login_threshold', 'notif_pengajuan_izin_guru', 'notif_belum_absen_pulang', 'notif_absen_masuk_admin'])->pluck('value', 'key');

        return view('dashboard.notifikasi_setting', compact('user', 'settings'));
    }

    public function store(Request $request)
    {
        wajibSuperadmin();
        $request->validate(['notif_login_threshold' => 'required|integer|min:1|max:20']);
        foreach ([
            'notif_login_mencurigakan' => $request->has('notif_login_mencurigakan') ? '1' : '0',
            'notif_login_threshold' => (string) $request->notif_login_threshold,
            'notif_pengajuan_izin_guru' => $request->has('notif_pengajuan_izin_guru') ? '1' : '0',
            'notif_belum_absen_pulang' => $request->has('notif_belum_absen_pulang') ? '1' : '0',
            'notif_absen_masuk_admin' => $request->has('notif_absen_masuk_admin') ? '1' : '0',
        ] as $key => $value) {
            DB::table('attendance_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
        }
        AuditLogger::record('update', 'attendance_settings', null, 'Pengaturan notifikasi diupdate', null, $request->except('_token'), $request);

        return back()->with('success', 'Pengaturan notifikasi berhasil disimpan.');
    }
}

