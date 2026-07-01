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
        // Halaman pengaturan menampilkan setting absensi yang sedang aktif.
        $user = session('user');

        // Semua setting diambil lewat service agar default tetap tersedia jika database belum lengkap.
        $settings = AttendanceSettingService::all();

        // Tahun ajaran ditampilkan agar admin bisa memilih periode aktif.
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranAktif = $tahunAjaran->firstWhere('aktif', 1) ?? $tahunAjaran->firstWhere('aktif', true);

        return view('dashboard.pengaturan', compact('user', 'settings', 'tahunAjaran', 'tahunAjaranAktif'));
    }

    public function store(Request $request)
    {
        // Validasi memastikan pengaturan yang disimpan masuk akal dan aman.
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

        // Setting lama dibaca agar logo lama tetap dipakai jika admin tidak upload logo baru.
        $before = AttendanceSettingService::all();
        $logoPath = $before['logo_sekolah'];

        // Jika admin upload logo baru, file dipindahkan ke public/img agar bisa diakses view.
        if ($request->hasFile('logo_sekolah')) {
            $filename = 'logo-sekolah-'.now()->format('YmdHis').'.'.$request->file('logo_sekolah')->getClientOriginalExtension();
            $request->file('logo_sekolah')->move(public_path('img'), $filename);
            $logoPath = 'img/'.$filename;
        }

        // Semua setting absensi disimpan sebagai key-value di tabel attendance_settings.
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

        // Jika admin memilih tahun ajaran aktif, tahun ajaran lain dinonaktifkan lebih dulu.
        if ($request->filled('tahun_ajaran_id')) {
            DB::table('tahun_ajarans')->update(['aktif' => false, 'updated_at' => now()]);
            DB::table('tahun_ajarans')->where('id', $request->tahun_ajaran_id)->update(['aktif' => true, 'updated_at' => now()]);
        }


        return back()->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
