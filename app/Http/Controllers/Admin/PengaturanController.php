<?php

namespace App\Http\Controllers\Admin;

use App\Support\AuditLogger;
use App\Http\Controllers\Controller;
use App\Services\AttendanceSettingService;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function index()
    {
        $user = session('user');
        $settings = AttendanceSettingService::all();

        return view('dashboard.pengaturan', compact('user', 'settings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_sekolah' => 'required|string|max:120',
            'logo_sekolah' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'jam_masuk' => 'required|date_format:H:i',
            'batas_telat' => 'required|date_format:H:i|after_or_equal:jam_masuk',
            'jam_pulang' => 'required|date_format:H:i|after:batas_telat',
            'masa_aktif_qr' => 'required|integer|min:1|max:240',
            'status_default_alfa' => 'required|in:alfa,alpa',
        ]);

        $before = AttendanceSettingService::all();
        $logoPath = $before['logo_sekolah'];

        if ($request->hasFile('logo_sekolah')) {
            $filename = 'logo-sekolah-'.now()->format('YmdHis').'.'.$request->file('logo_sekolah')->getClientOriginalExtension();
            $request->file('logo_sekolah')->move(public_path('img'), $filename);
            $logoPath = 'img/'.$filename;
        }

        AttendanceSettingService::setMany([
            'nama_sekolah' => $request->nama_sekolah,
            'logo_sekolah' => $logoPath,
            'jam_masuk' => $request->jam_masuk.':00',
            'batas_telat' => $request->batas_telat.':00',
            'jam_pulang' => $request->jam_pulang.':00',
            'masa_aktif_qr' => (string) $request->masa_aktif_qr,
            'status_default_alfa' => $request->status_default_alfa,
        ]);

        AuditLogger::record('update', 'attendance_settings', null, 'Pengaturan sistem diupdate', $before, AttendanceSettingService::all(), $request);

        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}

