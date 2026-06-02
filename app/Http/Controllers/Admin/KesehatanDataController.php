<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KesehatanDataController extends Controller
{
    public function index()
    {
        wajibSuperadmin();
        $user = session('user');
        $data = [
            'siswa_tanpa_kelas' => [
                'judul' => 'Siswa Tanpa Kelas',
                'masalah' => 'Siswa belum dimasukkan ke kelas, sehingga absensi dan rekap kelas bisa tidak terbaca.',
                'saran' => 'Buka menu Siswa, edit siswa, lalu pilih kelas yang benar.',
                'items' => User::where('role', 'siswa')->whereNull('kelas_id')->select('id', 'nama', 'nis', 'username', 'aktif')->get()
                    ->map(fn ($r) => ['id' => $r->id, 'utama' => $r->nama, 'detail' => 'NIS: '.($r->nis ?: '-').' | Username: '.$r->username, 'status' => $r->aktif ? 'Aktif' : 'Nonaktif']),
            ],
            'guru_tanpa_jadwal' => [
                'judul' => 'Guru Tanpa Jadwal',
                'masalah' => 'Guru belum punya jadwal mengajar aktif.',
                'saran' => 'Buka menu Jadwal, lalu tambahkan jadwal untuk guru tersebut jika memang mengajar.',
                'items' => User::where('role', 'guru')->whereNotIn('id', DB::table('jadwal_pelajarans')->whereNull('deleted_at')->pluck('guru_id'))->select('id', 'nama', 'nuptk', 'username', 'aktif')->get()
                    ->map(fn ($r) => ['id' => $r->id, 'utama' => $r->nama, 'detail' => 'NUPTK: '.($r->nuptk ?: '-').' | Username: '.$r->username, 'status' => $r->aktif ? 'Aktif' : 'Nonaktif']),
            ],
            'jadwal_tanpa_pengganti' => [
                'judul' => 'Jadwal Tanpa Guru Pengganti',
                'masalah' => 'Jika guru utama tidak hadir, jadwal ini belum punya guru pengganti.',
                'saran' => 'Edit jadwal pelajaran dan isi guru pengganti/inval.',
                'items' => DB::table('jadwal_pelajarans as j')->join('users as g', 'g.id', '=', 'j.guru_id')->join('kelas as k', 'k.id', '=', 'j.kelas_id')->join('mapels as m', 'm.id', '=', 'j.mapel_id')->whereNull('j.deleted_at')->whereNull('j.guru_pengganti_id')->select('j.id', 'j.hari', 'j.jam_mulai', 'j.jam_selesai', 'g.nama as guru', 'k.nama_kelas', 'm.nama_mapel')->get()
                    ->map(fn ($r) => ['id' => $r->id, 'utama' => $r->nama_kelas.' - '.$r->nama_mapel, 'detail' => 'Guru: '.$r->guru.' | '.ucfirst($r->hari).' '.$r->jam_mulai.'-'.$r->jam_selesai, 'status' => 'Belum ada pengganti']),
            ],
            'absensi_tanpa_tahun' => [
                'judul' => 'Absensi Tanpa Tahun Ajaran',
                'masalah' => 'Data absensi belum terhubung ke tahun ajaran, rekap semester bisa kurang rapi.',
                'saran' => 'Perbaiki data tahun ajaran pada absensi atau jalankan perapihan data.',
                'items' => DB::table('absensis as a')->join('users as s', 's.id', '=', 'a.id_siswa')->whereNull('a.deleted_at')->whereNull('a.tahun_ajaran_id')->select('a.id', 'a.tanggal', 'a.status_masuk', 'a.status_pulang', 's.nama')->limit(200)->get()
                    ->map(fn ($r) => ['id' => $r->id, 'utama' => $r->nama, 'detail' => 'Tanggal: '.$r->tanggal.' | Masuk: '.($r->status_masuk ?: '-').' | Pulang: '.($r->status_pulang ?: '-'), 'status' => 'Tahun ajaran kosong']),
            ],
            'pengajuan_menunggu' => [
                'judul' => 'Pengajuan Izin/Sakit Belum Direview',
                'masalah' => 'Pengajuan siswa belum disetujui atau ditolak.',
                'saran' => 'Buka menu Pengajuan Izin, lalu review pengajuan.',
                'items' => DB::table('student_permit_requests as p')->join('users as s', 's.id', '=', 'p.siswa_id')->whereNull('p.deleted_at')->where('p.status', 'menunggu')->select('p.id', 'p.tanggal_mulai', 'p.tanggal_selesai', 'p.jenis', 's.nama')->get()
                    ->map(fn ($r) => ['id' => $r->id, 'utama' => $r->nama, 'detail' => ucfirst($r->jenis).' | '.$r->tanggal_mulai.' s/d '.$r->tanggal_selesai, 'status' => 'Menunggu review']),
            ],
            'akun_nonaktif' => [
                'judul' => 'Akun Nonaktif',
                'masalah' => 'Akun tidak bisa login ke sistem.',
                'saran' => 'Aktifkan jika akun masih dipakai, atau biarkan jika memang sudah tidak digunakan.',
                'items' => User::where('aktif', 0)->select('id', 'nama', 'username', 'role')->get()
                    ->map(fn ($r) => ['id' => $r->id, 'utama' => $r->nama, 'detail' => 'Username: '.$r->username.' | Role: '.$r->role, 'status' => 'Nonaktif']),
            ],
            'arsip_baru' => [
                'judul' => 'Data Baru Diarsipkan',
                'masalah' => 'Ada data yang baru dihapus sementara dan masuk arsip.',
                'saran' => 'Buka menu Arsip Data untuk preview, restore, atau hapus permanen.',
                'items' => collect(tabelBisaArsip())->keys()->flatMap(fn ($t) => Schema::hasColumn($t, 'deleted_at') ? DB::table($t)->whereNotNull('deleted_at')->latest('deleted_at')->limit(10)->get()->map(fn ($r) => ['id' => $r->id, 'utama' => tabelBisaArsip()[$t] ?? $t, 'detail' => 'Tabel: '.$t.' | Diarsipkan: '.$r->deleted_at, 'status' => 'Diarsipkan']) : collect())->sortByDesc(fn ($r) => $r['detail'])->take(30)->values(),
            ],
        ];

        return view('dashboard.kesehatan_data', compact('user', 'data'));
    }
}
