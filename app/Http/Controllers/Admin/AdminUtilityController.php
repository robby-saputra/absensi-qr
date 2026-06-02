<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'jadwal_digantikan' => 'Jadwal digantikan',
            'guru_piket_pengganti' => 'Guru piket pengganti',
            'login_mencurigakan' => 'Login mencurigakan',
        ];

        $jadwalDigantikan = DB::table('jadwal_pelajarans as j')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->where('j.status_guru', 'digantikan')
            ->select(
                'j.id',
                'j.hari',
                'j.jam_mulai',
                'j.jam_selesai',
                'j.alasan_tidak_hadir',
                'j.updated_at',
                'g.nama as guru_utama',
                'gp.nama as guru_pengganti',
                'k.nama_kelas',
                'm.nama_mapel'
            )
            ->latest('j.updated_at')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'kategori' => 'jadwal_digantikan',
                    'tipe' => 'Jadwal digantikan',
                    'severity' => 'info',
                    'judul' => 'Guru '.$item->guru_utama.' '.($item->alasan_tidak_hadir ?? 'tidak hadir'),
                    'utama' => $item->guru_utama,
                    'pengganti' => $item->guru_pengganti ?? '-',
                    'alasan' => $item->alasan_tidak_hadir ?? '-',
                    'detail' => $item->nama_mapel.' | '.$item->nama_kelas,
                    'waktu' => $item->hari.', '.$item->jam_mulai.' - '.$item->jam_selesai,
                    'created_at' => $item->updated_at,
                ];
            });

        $guruPiketDigantikan = DB::table('guru_pikets as gp')
            ->join('users as g', 'g.id', '=', 'gp.guru_id')
            ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
            ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
            ->whereIn('gp.status', ['Izin', 'Sakit'])
            ->select(
                'gp.*',
                'g.nama as guru_utama',
                'g1.nama as guru_pengganti',
                'g2.nama as guru_pengganti2'
            )
            ->latest('gp.updated_at')
            ->get()
            ->map(function ($item) {
                $pengganti = collect([
                    $item->guru_pengganti,
                    $item->guru_pengganti2,
                ])->filter()->implode(', ');

                return (object) [
                    'kategori' => 'guru_piket_pengganti',
                    'tipe' => 'Guru piket pengganti',
                    'severity' => 'warning',
                    'judul' => 'Guru piket '.$item->guru_utama.' '.$item->status,
                    'utama' => $item->guru_utama,
                    'pengganti' => $pengganti ?: '-',
                    'alasan' => $item->status,
                    'detail' => 'Tugas guru piket',
                    'waktu' => ucfirst($item->hari).', '.$item->jam_mulai.' - '.$item->jam_selesai,
                    'created_at' => $item->updated_at,
                ];
            });

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
                        'pengganti' => $payload['siswa'] ?? ($matches[2] ?? '-'),
                        'alasan' => 'Masuk: '.($payload['status_masuk'] ?? ($matches[5] ?? '-')).' | Pulang: '.($payload['status_pulang'] ?? ($matches[6] ?? '-')),
                        'detail' => 'Kelas: '.($payload['kelas'] ?? ($matches[3] ?? '-')),
                        'waktu' => $payload['tanggal'] ?? ($matches[4] ?? '-'),
                        'label_utama' => 'Guru mapel',
                        'label_pengganti' => 'Siswa',
                        'label_alasan' => 'Status',
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
                        'pengganti' => $payload['guru_pengganti'] ?? '-',
                        'alasan' => $payload['alasan'] ?? '-',
                        'detail' => 'Kelas: '.($payload['kelas'] ?? '-'),
                        'waktu' => $payload['jam'] ?? '-',
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
                        'pengganti' => $payload['kelas'] ?? '-',
                        'alasan' => ucfirst($payload['status'] ?? '-'),
                        'detail' => $item->pesan,
                        'waktu' => ($payload['tanggal'] ?? '-').' '.($payload['jam'] ?? ''),
                        'label_utama' => 'Siswa',
                        'label_pengganti' => 'Kelas',
                        'label_alasan' => 'Status',
                        'created_at' => $item->created_at,
                    ];
                }

                if ($kategori === 'jadwal_digantikan') {
                    return (object) [
                        'kategori' => 'jadwal_digantikan',
                        'tipe' => 'Jadwal digantikan',
                        'severity' => $item->severity ?? 'info',
                        'judul' => $item->judul,
                        'utama' => $payload['guru_utama'] ?? '-',
                        'pengganti' => '-',
                        'alasan' => 'Pengganti mapel',
                        'detail' => 'Kelas: '.($payload['kelas'] ?? '-'),
                        'waktu' => $payload['jam'] ?? '-',
                        'label_pengganti' => 'Info',
                        'created_at' => $item->created_at,
                    ];
                }

                if ($kategori === 'guru_piket_pengganti') {
                    return (object) [
                        'kategori' => 'guru_piket_pengganti',
                        'tipe' => 'Guru piket pengganti',
                        'severity' => $item->severity ?? 'warning',
                        'judul' => $item->judul,
                        'utama' => $payload['guru_utama'] ?? '-',
                        'pengganti' => $payload['guru_pengganti'] ?? '-',
                        'alasan' => $payload['alasan'] ?? '-',
                        'detail' => 'Jadwal dialihkan: '.($payload['jadwal_dialihkan'] ?? '-'),
                        'waktu' => ($payload['hari'] ?? '-').', '.($payload['jam'] ?? '-'),
                        'created_at' => $item->created_at,
                    ];
                }

                if ($kategori === 'login_mencurigakan') {
                    return (object) [
                        'kategori' => 'login_mencurigakan',
                        'tipe' => 'Login mencurigakan',
                        'severity' => 'danger',
                        'judul' => $item->judul,
                        'utama' => $payload['username'] ?? '-',
                        'pengganti' => $payload['ip_address'] ?? '-',
                        'alasan' => ($payload['total_gagal'] ?? '-').' percobaan gagal',
                        'detail' => $item->pesan,
                        'waktu' => $payload['waktu'] ?? '-',
                        'label_utama' => 'Username',
                        'label_pengganti' => 'IP',
                        'label_alasan' => 'Percobaan',
                        'created_at' => $item->created_at,
                    ];
                }

                return (object) [
                    'kategori' => $kategori ?: 'sistem',
                    'tipe' => 'Sistem',
                    'severity' => $item->severity ?? 'info',
                    'judul' => $item->judul ?? 'Notifikasi',
                    'utama' => '-',
                    'pengganti' => '-',
                    'alasan' => '-',
                    'detail' => $item->pesan,
                    'waktu' => '-',
                    'label_utama' => 'Guru utama',
                    'label_pengganti' => 'Guru pengganti',
                    'label_alasan' => 'Alasan',
                    'created_at' => $item->created_at,
                ];
            });

        $notifikasi = $jadwalDigantikan
            ->merge($guruPiketDigantikan)
            ->merge($notifikasiManual)
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
}
