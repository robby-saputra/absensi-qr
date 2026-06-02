<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
