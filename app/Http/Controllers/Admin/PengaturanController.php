<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengaturanController extends Controller
{
    public function index()
    {
        $user = session('user');
        $settings = AttendanceSettingService::all();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranAktif = $tahunAjaran->firstWhere('aktif', 1) ?? $tahunAjaran->firstWhere('aktif', true);

        return view('dashboard.pengaturan', compact('user', 'settings', 'tahunAjaran', 'tahunAjaranAktif'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_sekolah' => 'required|string|max:120',
            'logo_sekolah' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'jam_masuk' => 'required|date_format:H:i',
            'batas_telat' => 'required|date_format:H:i|after_or_equal:jam_masuk',
            'jam_pulang' => 'required|date_format:H:i|after:batas_telat',
            'jam_kunci_absensi' => 'required|date_format:H:i',
            'masa_aktif_qr' => 'required|integer|min:1|max:240',
            'status_default_alfa' => 'required|in:alfa,alpa',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'latitude_sekolah' => 'required|numeric|between:-90,90',
            'longitude_sekolah' => 'required|numeric|between:-180,180',
            'radius_absensi' => 'required|integer|min:1|max:5000',
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
            'jam_kunci_absensi' => $request->jam_kunci_absensi.':00',
            'masa_aktif_qr' => (string) $request->masa_aktif_qr,
            'status_default_alfa' => $request->status_default_alfa,
            'latitude_sekolah' => (string) $request->latitude_sekolah,
            'longitude_sekolah' => (string) $request->longitude_sekolah,
            'radius_absensi' => (string) $request->radius_absensi,
        ]);

        if ($request->filled('tahun_ajaran_id')) {
            DB::table('tahun_ajarans')->update(['aktif' => false, 'updated_at' => now()]);
            DB::table('tahun_ajarans')->where('id', $request->tahun_ajaran_id)->update(['aktif' => true, 'updated_at' => now()]);
        }


        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
