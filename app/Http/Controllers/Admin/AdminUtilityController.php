<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminUtilityController extends Controller
{
    public function onlineUsers()
    {
        $offlineLimit = now()->subMinutes(2);

        DB::table('user_login_statuses')
            ->where('is_online', true)
            ->where('last_seen_at', '<', $offlineLimit)
            ->update([
                'is_online' => false,
                'logout_at' => now(),
                'updated_at' => now(),
            ]);

        $users = DB::table('user_login_statuses as s')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('kelas as k', 'k.id', '=', 'u.kelas_id')
            ->where('s.is_online', true)
            ->where('s.last_seen_at', '>=', $offlineLimit)
            ->select(
                'u.id',
                'u.nama',
                'u.nama_ortu',
                'u.username',
                's.role',
                'k.nama_kelas',
                's.login_at',
                's.last_seen_at',
                's.ip_address'
            )
            ->orderByDesc('s.last_seen_at')
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'nama' => $row->role === 'orang_tua' ? ($row->nama_ortu ?: 'Orang Tua '.$row->nama) : $row->nama,
                    'username' => $row->username,
                    'role' => ucfirst($row->role),
                    'kelas' => $row->nama_kelas ?: '-',
                    'login_at' => optional($row->login_at ? Carbon::parse($row->login_at) : null)->format('H:i:s') ?: '-',
                    'last_seen_at' => optional($row->last_seen_at ? Carbon::parse($row->last_seen_at) : null)->diffForHumans() ?: '-',
                    'ip_address' => $row->ip_address ?: '-',
                ];
            });

        return response()->json([
            'total' => $users->count(),
            'users' => $users,
            'checked_at' => now()->format('H:i:s'),
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'resource' => 'required|string',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $result = hapusMassalAdmin($request->resource, $request->ids, $request);

        if (($result['deleted'] ?? 0) < 1) {
            return back()->with('error', $result['message'] ?? 'Tidak ada data yang berhasil dihapus.');
        }

        return back()->with('success', $result['message']);
    }

    public function notifikasi()
    {
        $user = session('user');
        $kategoriAktif = request('kategori', 'semua');
        $labelKategori = [
            'semua' => 'Semua',
            'absensi_masuk_siswa' => 'Absensi masuk siswa',
            'absensi_siswa_diubah' => 'Absensi siswa diubah',
            'guru_tidak_hadir' => 'Guru tidak hadir',
            'guru_pengganti_tidak_hadir' => 'Guru pengganti tidak hadir',
        ];

        $notifikasiManual = DB::table('notifications')
            ->whereNull('user_id')
            ->latest('id')
            ->limit(80)
            ->get()
            ->map(function ($item) {
                $payload = [];
                if (! empty($item->payload)) {
                    $payload = json_decode($item->payload, true) ?: [];
                }

                $kategori = $item->kategori ?? null;
                if ($item->judul === 'Absensi Siswa Diubah Guru Mapel') {
                    preg_match(
                        '/^(.*?) mengubah absensi (.*?) kelas (.*?) tanggal (.*?)\. Status masuk: (.*?), status pulang: (.*?)\.$/',
                        $item->pesan ?? '',
                        $matches
                    );

                    return (object) [
                        'kategori' => 'absensi_siswa_diubah',
                        'tipe' => 'Absensi siswa diubah',
                        'severity' => $item->severity ?? 'info',
                        'judul' => $item->judul,
                        'utama' => $payload['guru_mapel'] ?? ($matches[1] ?? '-'),
                        'detail_info' => $payload['siswa'] ?? ($matches[2] ?? '-'),
                        'alasan' => 'Masuk: '.($payload['status_masuk'] ?? ($matches[5] ?? '-')).' | Pulang: '.($payload['status_pulang'] ?? ($matches[6] ?? '-')),
                        'detail' => 'Kelas: '.($payload['kelas'] ?? ($matches[3] ?? '-')),
                        'waktu' => $payload['tanggal'] ?? ($matches[4] ?? '-'),
                        'label_utama' => 'Guru mapel',
                        'label_detail' => 'Siswa',
                        'label_alasan' => 'Status',
                        'action_url' => '/dashboard/admin/notifikasi?kategori=absensi_siswa_diubah',
                        'action_label' => 'Lihat Notifikasi',
                        'created_at' => $item->created_at,
                    ];
                }

                if ($kategori === 'guru_tidak_hadir') {
                    return (object) [
                        'kategori' => 'guru_tidak_hadir',
                        'tipe' => 'Guru tidak hadir',
                        'severity' => $item->severity ?? 'warning',
                        'judul' => $item->judul,
                        'utama' => $payload['guru_utama'] ?? '-',
                        'detail_info' => '-',
                        'alasan' => $payload['alasan'] ?? '-',
                        'detail' => 'Kelas: '.($payload['kelas'] ?? '-'),
                        'waktu' => $payload['jam'] ?? '-',
                        'action_url' => '/dashboard/admin/jadwal',
                        'action_label' => 'Lihat Jadwal',
                        'created_at' => $item->created_at,
                    ];
                }

                if ($kategori === 'guru_pengganti_tidak_hadir') {
                    $jadwalId = $item->source_id ?? ($payload['jadwal_id'] ?? null);

                    return (object) [
                        'kategori' => 'guru_pengganti_tidak_hadir',
                        'tipe' => 'Guru pengganti tidak hadir',
                        'severity' => $item->severity ?? 'danger',
                        'judul' => $item->judul,
                        'utama' => $payload['guru_pengganti'] ?? '-',
                        'detail_info' => $payload['guru_utama'] ?? '-',
                        'alasan' => 'Butuh pengganti lanjutan',
                        'detail' => ($payload['mapel'] ?? '-').' | Kelas: '.($payload['kelas'] ?? '-'),
                        'waktu' => ($payload['tanggal'] ?? '-').' '.($payload['jam'] ?? ''),
                        'label_utama' => 'Guru pengganti',
                        'label_detail' => 'Guru utama',
                        'label_alasan' => 'Status',
                        'action_url' => $jadwalId ? '/dashboard/admin/jadwal/edit/'.$jadwalId : '/dashboard/admin/jadwal',
                        'action_label' => 'Ganti Guru Pengganti',
                        'created_at' => $item->created_at,
                    ];
                }

                if ($kategori === 'absensi_masuk_siswa') {
                    return (object) [
                        'kategori' => 'absensi_masuk_siswa',
                        'tipe' => 'Absensi masuk',
                        'severity' => $item->severity ?? 'success',
                        'judul' => $item->judul,
                        'utama' => $payload['siswa'] ?? '-',
                        'detail_info' => $payload['kelas'] ?? '-',
                        'alasan' => ucfirst($payload['status'] ?? '-'),
                        'detail' => $item->pesan,
                        'waktu' => ($payload['tanggal'] ?? '-').' '.($payload['jam'] ?? ''),
                        'label_utama' => 'Siswa',
                        'label_detail' => 'Kelas',
                        'label_alasan' => 'Status',
                        'action_url' => '/dashboard/admin/absensi',
                        'action_label' => 'Lihat Absensi',
                        'created_at' => $item->created_at,
                    ];
                }

                return (object) [
                    'kategori' => $kategori ?: 'sistem',
                    'tipe' => 'Sistem',
                    'severity' => $item->severity ?? 'info',
                    'judul' => $item->judul ?? 'Notifikasi',
                    'utama' => '-',
                    'detail_info' => '-',
                    'alasan' => '-',
                    'detail' => $item->pesan,
                    'waktu' => '-',
                    'label_utama' => 'Guru utama',
                    'label_detail' => 'Detail',
                    'label_alasan' => 'Alasan',
                    'action_url' => '/dashboard/admin/notifikasi?kategori='.($kategori ?: 'sistem'),
                    'action_label' => 'Lihat Detail',
                    'created_at' => $item->created_at,
                ];
            });

        $notifikasi = $notifikasiManual
            ->sortByDesc('created_at')
            ->values();

        $ringkasan = collect($labelKategori)
            ->except('semua')
            ->mapWithKeys(fn ($label, $key) => [$key => $notifikasi->where('kategori', $key)->count()]);

        if ($kategoriAktif !== 'semua') {
            $notifikasi = $notifikasi->where('kategori', $kategoriAktif)->values();
        }

        DB::table('notifications')
            ->whereNull('user_id')
            ->where('status', 'belum_dibaca')
            ->update([
                'status' => 'dibaca',
                'updated_at' => now(),
            ]);

        return view('dashboard.notifikasi', compact('user', 'notifikasi', 'labelKategori', 'kategoriAktif', 'ringkasan'));
    }

    public function bacaNotifikasi()
    {
        if (! Schema::hasTable('notifications') || ! Schema::hasColumn('notifications', 'status')) {
            return response()->json(['status' => 'success', 'unread' => 0]);
        }

        DB::table('notifications')
            ->whereNull('user_id')
            ->where('status', 'belum_dibaca')
            ->update([
                'status' => 'dibaca',
                'updated_at' => now(),
            ]);

        return response()->json(['status' => 'success', 'unread' => 0]);
    }
}
