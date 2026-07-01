<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controller ini menyiapkan halaman rekap admin untuk guru piket, absensi mapel, dan jadwal guru.
class RekapAdminController extends Controller
{
    // Menampilkan rekap status guru piket pada tanggal dan filter yang dipilih.
    public function guruPiket(Request $request)
    {
        $user = session('user');
        $hari = $request->get('hari');
        $status = $request->get('status');
        $tanggal = $request->get('tanggal', now()->toDateString());

        // Query ini menggabungkan jadwal piket dengan status kehadiran harian.
        $query = DB::table('guru_pikets as gp')
            ->join('users as g', 'g.id', '=', 'gp.guru_id')
            ->leftJoin('guru_piket_statuses as gps', function ($join) use ($tanggal) {
                $join->on('gps.guru_piket_id', '=', 'gp.id')->on('gps.guru_id', '=', 'gp.guru_id')->whereDate('gps.tanggal', $tanggal)->whereNull('gps.deleted_at');
            })
            ->whereNull('gp.deleted_at')
            ->select(
                'gp.*',
                'g.nama as guru_utama',
                DB::raw("COALESCE(gps.status, 'belum_konfirmasi') as status_harian"),
                'gps.waktu_scan_masuk', 'gps.waktu_scan_keluar', 'gps.waktu_konfirmasi'
            );

        if ($hari) {
            $query->where('gp.hari', strtolower($hari));
        }

        if ($status) {
            $query->whereRaw("COALESCE(gps.status, 'belum_konfirmasi') = ?", [$status]);
        }

        $data = $query
            ->orderBy('gp.hari')
            ->orderBy('gp.jam_mulai')
            ->orderBy('g.nama')
            ->get();

        return view('dashboard.rekap.guru_piket', compact('user', 'data', 'hari', 'status', 'tanggal'));
    }

    // Menampilkan rekap absensi mapel berdasarkan tanggal, kelas, mapel, JP, dan status.
    public function absensiMapel(Request $request)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now()->toDateString());
        $kelasId = $request->get('kelas_id');
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: DB::table('tahun_ajarans')->where('aktif', true)->value('id');
        $filters = [
            'tanggal' => $tanggal,
            'kelas_id' => $kelasId,
            'tahun_ajaran_id' => $tahunAjaranId,
            'mapel_id' => $request->get('mapel_id'),
            'jp' => $request->get('jp'),
            'status' => $request->get('status'),
            'search' => trim((string) $request->get('search', '')),
        ];

        $hari = strtolower(Carbon::parse($tanggal)->locale('id')->translatedFormat('l'));
        // Query ini membandingkan jadwal mapel, absensi harian, dan absensi mapel siswa.
        $query = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('users as s', function ($join) {
                $join->on('s.kelas_id', '=', 'j.kelas_id')->where('s.role', 'siswa')->where('s.aktif', 1)->whereNull('s.deleted_at');
            })
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as gp', 'gp.id', '=', 'j.guru_pengganti_id')
            ->leftJoin('users as gpel', 'gpel.id', '=', 'a.guru_pelaksana_id')
            ->leftJoin('absensi_mapels as a', function ($join) use ($tanggal) {
                $join->on('a.jadwal_id', '=', 'j.id')->on('a.siswa_id', '=', 's.id')->whereDate('a.tanggal', $tanggal)->whereNull('a.deleted_at');
            })
            ->leftJoin('absensis as ah', function ($join) use ($tanggal) {
                $join->on('ah.id_siswa', '=', 's.id')->whereDate('ah.tanggal', $tanggal)->whereNull('ah.deleted_at');
            })
            ->leftJoin('jurusan as jr', 'jr.id', '=', 'k.jurusan_id')
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggal) {
                $join->on('jgs.jadwal_id', '=', 'j.id')->whereDate('jgs.tanggal', $tanggal);
            })
            ->select(
                'a.id', 'a.tanggal as tanggal_absensi', 'a.jam_scan', 'a.status', 'a.catatan_guru', 'a.updated_at',
                'j.id as jadwal_id',
                's.nama as nama_siswa',
                's.id as siswa_id',
                's.nis',
                'k.nama_kelas',
                'jr.nama_jurusan',
                'm.nama_mapel',
                'm.id as mapel_id',
                'g.nama as guru_utama',
                'gpel.nama as guru_pelaksana',
                'a.role_guru_pelaksana',
                'gp.nama as guru_pengganti',
                'j.status_guru',
                'j.alasan_tidak_hadir',
                'j.hari',
                'j.jam_mulai',
                'j.jam_selesai',
                'j.jam_ke_mulai',
                'j.jumlah_jp',
                'jgs.status_guru as status_guru_harian',
                'jgs.pengganti_status',
                'ah.jam_masuk as jam_harian_masuk', 'ah.status_masuk as status_harian_masuk',
                'ah.jam_pulang as jam_harian_pulang', 'ah.status_pulang as status_harian_pulang'
            );

        $query->whereNull('j.deleted_at')->whereNull('k.deleted_at');

        $query->whereNull('j.deleted_at')->whereNull('k.deleted_at')->whereRaw('LOWER(j.hari) = ?', [$hari])
            ->whereNotNull('j.jam_ke_mulai')->whereNotNull('j.jumlah_jp');

        if ($kelasId) {
            $query->where('s.kelas_id', $kelasId);
        }

        $query->when($filters['mapel_id'], fn ($query, $id) => $query->where('j.mapel_id', $id))
            ->when($filters['jp'], fn ($query, $jp) => $query->where('j.jam_ke_mulai', '<=', $jp)->whereRaw('(j.jam_ke_mulai + j.jumlah_jp - 1) >= ?', [$jp]))
            ->when($filters['status'] === 'belum', fn ($query) => $query->whereNull('a.id'))
            ->when($filters['status'] && $filters['status'] !== 'belum', fn ($query) => $query->where('a.status', $filters['status']))
            ->when($filters['search'], fn ($query, $search) => $query->where(function ($where) use ($search) {
                $where->where('s.nama', 'like', '%'.$search.'%')->orWhere('s.nis', 'like', '%'.$search.'%')
                    ->orWhere('g.nama', 'like', '%'.$search.'%')->orWhere('gp.nama', 'like', '%'.$search.'%');
            }));

        if ($tahunAjaranId) {
            $query->where(function ($tahun) use ($tahunAjaranId) {
                $tahun->where('j.tahun_ajaran_id', $tahunAjaranId)->orWhereNull('j.tahun_ajaran_id');
            });
        }

        $data = $query
            ->orderByRaw('CASE WHEN a.id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('a.updated_at')
            ->orderBy('k.nama_kelas')
            ->orderBy('j.jam_ke_mulai')
            ->orderBy('s.nama')
            ->get()
            ->each(function ($row) use ($tanggal) {
                $row->tanggal = $row->tanggal_absensi ?: $tanggal;
                $row->status = $row->status ?: 'belum';
            });

        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $mapel = tanpaArsip(DB::table('mapels'), 'mapels')->orderBy('nama_mapel')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $ringkasan = [
            'total' => $data->count(),
            'hadir' => $data->where('status', 'hadir')->count(),
            'telat' => $data->filter(fn ($row) => in_array($row->status, ['telat', 'terlambat']))->count(),
            'izin_sakit' => $data->filter(fn ($row) => in_array($row->status, ['izin', 'sakit']))->count(),
            'alfa' => $data->filter(fn ($row) => in_array($row->status, ['alfa', 'alpa']))->count(),
            'belum' => $data->where('status', 'belum')->count(),
        ];

        return view('dashboard.rekap.absensi_mapel', compact('user', 'data', 'tanggal', 'kelasId', 'kelas', 'mapel', 'tahunAjaran', 'tahunAjaranId', 'filters', 'ringkasan'));
    }

    public function jadwalGuruMapel(Request $request)
    {
        $user = session('user');
        $guruId = $request->get('guru_id');
        $hari = $request->get('hari');

        $query = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as guru_utama'
            );

        if ($guruId) {
            $query->where('j.guru_id', $guruId);
        }

        if ($hari) {
            $query->where('j.hari', $hari);
        }

        $data = $query
            ->orderBy('g.nama')
            ->orderBy('j.hari')
            ->orderBy('j.jam_mulai')
            ->get();

        $guru = User::where('role', 'guru')->whereNull('deleted_at')->orderBy('nama')->get();

        return view('dashboard.rekap.jadwal_guru_mapel', compact('user', 'data', 'guru', 'guruId', 'hari'));
    }
}
