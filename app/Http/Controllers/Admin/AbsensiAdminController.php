<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceAuditService;
use App\Services\SubjectAttendanceTeacherService;
use App\Support\AbsensiRekapSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Controller ini mengelola data absensi harian dan absensi mapel dari sisi admin.
class AbsensiAdminController extends Controller
{
    // Halaman utama absensi diarahkan ke rekap agar admin melihat laporan yang sudah difilter.
    public function index(Request $request)
    {
        wajibSuperadmin();

        return redirect()->route('rekap.absensi', $request->query());

        // Kode di bawah ini adalah alur lama untuk daftar absensi, masih tersimpan sebagai referensi sistem.
        $user = session('user');
        $filters = $request->only(['tanggal', 'kelas_id', 'status', 'search', 'tahun_ajaran_id']);
        $tahunAjaranId = $filters['tahun_ajaran_id'] ?? tahunAjaranAktifId();
        $tahunAjaranFilter = $tahunAjaranId
            ? DB::table('tahun_ajarans')->where('id', $tahunAjaranId)->first()
            : null;

        $query = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->select('a.*', 's.nama as nama_siswa', 's.nis', 'k.nama_kelas', 'j.nama_jurusan');
        tanpaArsip($query, 'absensis', 'a');

        if (! empty($filters['tanggal'])) {
            $query->whereDate('a.tanggal', $filters['tanggal']);
        }

        if (! empty($filters['kelas_id'])) {
            $query->where('s.kelas_id', $filters['kelas_id']);
        }

        if (! empty($filters['status'])) {
            $query->where(function ($status) use ($filters) {
                $status->where('a.status_masuk', $filters['status'])
                    ->orWhere('a.status_pulang', $filters['status']);
            });
        }

        if (! empty($filters['search'])) {
            $query->where(function ($search) use ($filters) {
                $search->where('s.nama', 'like', '%'.$filters['search'].'%')
                    ->orWhere('s.nis', 'like', '%'.$filters['search'].'%');
            });
        }

        if ($tahunAjaranId && Schema::hasColumn('absensis', 'tahun_ajaran_id')) {
            $query->where(function ($tahun) use ($tahunAjaranId, $tahunAjaranFilter) {
                $tahun->where('a.tahun_ajaran_id', $tahunAjaranId);

                if ($tahunAjaranFilter) {
                    $tahun->orWhere(function ($legacy) use ($tahunAjaranFilter) {
                        $legacy->whereNull('a.tahun_ajaran_id')
                            ->whereDate('a.tanggal', '>=', $tahunAjaranFilter->tanggal_mulai)
                            ->whereDate('a.tanggal', '<=', $tahunAjaranFilter->tanggal_selesai);
                    });
                }
            });
        }

        $data = $query->latest('a.tanggal')->orderBy('k.nama_kelas')->orderBy('s.nama')->paginate(25)->withQueryString();
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();

        return view('dashboard.absensi_admin.index', compact('user', 'data', 'kelas', 'tahunAjaran', 'filters', 'tahunAjaranId'));
    }

    public function create(Request $request)
    {
        wajibSuperadmin();

        $user = session('user');
        $absensi = (object) [
            'id_siswa' => $request->get('id_siswa'),
            'tanggal' => $request->get('tanggal', now()->toDateString()),
            'jam_masuk' => null,
            'jam_pulang' => null,
            'status_masuk' => $request->get('status_masuk'),
            'status_pulang' => null,
        ];
        $mode = 'create';
        $siswa = siswaAktifQuery()->orderBy('nama')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = tahunAjaranAktifId();

        return view('dashboard.absensi_admin.form', compact('user', 'absensi', 'mode', 'siswa', 'tahunAjaran', 'tahunAjaranId'));
    }

    public function sinkronRekap(Request $request)
    {
        wajibSuperadmin();

        $tanggal = $request->filled('tanggal') ? $request->tanggal : null;
        $tahunAjaranId = $request->filled('tahun_ajaran_id') ? (int) $request->tahun_ajaran_id : null;
        $result = AbsensiRekapSync::harian($tanggal, $tahunAjaranId);

        return back()->with('success', $result['message']);
    }

    public function store(Request $request)
    {
        wajibSuperadmin();

        $request->validate([
            'id_siswa' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable',
            'jam_pulang' => 'nullable',
            'status_masuk' => 'nullable|string|max:30',
            'status_pulang' => 'nullable|string|max:30',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        ]);

        if (! akunAktifRole($request->id_siswa, 'siswa')) {
            return back()->withInput()->with('error', 'Siswa nonaktif tidak bisa dibuatkan absensi berjalan.');
        }

        $exists = DB::table('absensis')
            ->where('id_siswa', $request->id_siswa)
            ->whereDate('tanggal', $request->tanggal)
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Absensi harian siswa pada tanggal ini sudah ada. Silakan edit data yang sudah ada.');
        }

        $payload = [
            'id_siswa' => $request->id_siswa,
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk ?: null,
            'jam_pulang' => $request->jam_pulang ?: null,
            'status_masuk' => $request->status_masuk ?: null,
            'status_pulang' => $request->status_pulang ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('absensis', 'tahun_ajaran_id')) {
            $payload['tahun_ajaran_id'] = $request->tahun_ajaran_id ?: tahunAjaranAktifId();
        }

        $id = DB::table('absensis')->insertGetId($payload);

        return redirect('/dashboard/admin/absensi')->with('success', 'Absensi harian berhasil ditambahkan.');
    }

    public function show($id)
    {
        wajibSuperadmin();

        $user = session('user');
        $absensi = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'a.tahun_ajaran_id')
            ->select('a.*', 's.nama as nama_siswa', 's.nis', 'k.nama_kelas', 'j.nama_jurusan', 'ta.nama as tahun_ajaran', 'ta.semester')
            ->where('a.id', $id)
            ->first();

        abort_if(! $absensi, 404);

        return view('dashboard.absensi_admin.view', compact('user', 'absensi'));
    }

    public function edit($id)
    {
        wajibSuperadmin();

        $user = session('user');
        $absensi = DB::table('absensis')->where('id', $id)->first();
        abort_if(! $absensi, 404);

        $mode = 'edit';
        $siswa = siswaAktifQuery()->orderBy('nama')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $absensi->tahun_ajaran_id ?? tahunAjaranAktifId();

        return view('dashboard.absensi_admin.form', compact('user', 'absensi', 'mode', 'siswa', 'tahunAjaran', 'tahunAjaranId'));
    }

    public function update(Request $request, $id)
    {
        wajibSuperadmin();

        $request->validate([
            'id_siswa' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'jam_masuk' => 'nullable',
            'jam_pulang' => 'nullable',
            'status_masuk' => 'nullable|string|max:30',
            'status_pulang' => 'nullable|string|max:30',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        ]);

        if (! akunAktifRole($request->id_siswa, 'siswa')) {
            return back()->withInput()->with('error', 'Siswa nonaktif tidak bisa dipakai untuk absensi berjalan.');
        }

        $old = DB::table('absensis')->where('id', $id)->first();
        abort_if(! $old, 404);

        $duplicate = DB::table('absensis')
            ->where('id', '!=', $id)
            ->where('id_siswa', $request->id_siswa)
            ->whereDate('tanggal', $request->tanggal)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Data ganda ditolak. Siswa ini sudah punya absensi harian pada tanggal tersebut.');
        }

        $payload = [
            'id_siswa' => $request->id_siswa,
            'tanggal' => $request->tanggal,
            'jam_masuk' => $request->jam_masuk ?: null,
            'jam_pulang' => $request->jam_pulang ?: null,
            'status_masuk' => $request->status_masuk ?: null,
            'status_pulang' => $request->status_pulang ?: null,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('absensis', 'tahun_ajaran_id')) {
            $payload['tahun_ajaran_id'] = $request->tahun_ajaran_id ?: tahunAjaranAktifId();
        }

        DB::table('absensis')->where('id', $id)->update($payload);

        return redirect('/dashboard/admin/absensi')->with('success', 'Absensi harian berhasil diperbarui.');
    }

    public function delete(Request $request, $id)
    {
        wajibSuperadmin();

        $old = DB::table('absensis')->where('id', $id)->first();
        abort_if(! $old, 404);

        if (! hapusDataAdmin('absensis', (int) $id, 'Absensi harian', $request)) {
            return back()->with('error', 'Absensi harian gagal dihapus atau data tidak ditemukan.');
        }

        return back()->with('success', 'Absensi harian berhasil dipindahkan ke arsip.');
    }

    public function mapelIndex(Request $request)
    {
        wajibSuperadmin();

        return redirect('/dashboard/admin/rekap/absensi-mapel'.($request->getQueryString() ? '?'.$request->getQueryString() : ''));

        $user = session('user');
        $filters = $request->only(['tanggal', 'kelas_id', 'status', 'search', 'tahun_ajaran_id']);
        $tahunAjaranId = $filters['tahun_ajaran_id'] ?? tahunAjaranAktifId();
        $tahunAjaranFilter = $tahunAjaranId
            ? DB::table('tahun_ajarans')->where('id', $tahunAjaranId)->first()
            : null;

        $query = DB::table('absensi_mapels as a')
            ->join('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->join('jadwal_pelajarans as jp', 'jp.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->select(
                'a.*',
                's.nama as nama_siswa',
                's.nis',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as guru_utama',
                'jp.hari',
                'jp.jam_mulai',
                'jp.jam_selesai',
                'jp.jam_ke_mulai',
                'jp.jumlah_jp',
                'jp.status_guru'
            );
        tanpaArsip($query, 'absensi_mapels', 'a');

        if (! empty($filters['tanggal'])) {
            $query->whereDate('a.tanggal', $filters['tanggal']);
        }

        if (! empty($filters['kelas_id'])) {
            $query->where('s.kelas_id', $filters['kelas_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('a.status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function ($search) use ($filters) {
                $search->where('s.nama', 'like', '%'.$filters['search'].'%')
                    ->orWhere('s.nis', 'like', '%'.$filters['search'].'%')
                    ->orWhere('m.nama_mapel', 'like', '%'.$filters['search'].'%')
                    ->orWhere('g.nama', 'like', '%'.$filters['search'].'%');
            });
        }

        if ($tahunAjaranId && Schema::hasColumn('absensi_mapels', 'tahun_ajaran_id')) {
            $query->where(function ($tahun) use ($tahunAjaranId, $tahunAjaranFilter) {
                $tahun->where('a.tahun_ajaran_id', $tahunAjaranId);

                if ($tahunAjaranFilter) {
                    $tahun->orWhere(function ($legacy) use ($tahunAjaranFilter) {
                        $legacy->whereNull('a.tahun_ajaran_id')
                            ->whereDate('a.tanggal', '>=', $tahunAjaranFilter->tanggal_mulai)
                            ->whereDate('a.tanggal', '<=', $tahunAjaranFilter->tanggal_selesai);
                    });
                }
            });
        }

        $data = $query->latest('a.tanggal')->orderBy('k.nama_kelas')->orderBy('s.nama')->paginate(25)->withQueryString();
        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();

        return view('dashboard.absensi_mapel_admin.index', compact('user', 'data', 'kelas', 'tahunAjaran', 'filters', 'tahunAjaranId'));
    }

    public function mapelCreate(Request $request)
    {
        wajibSuperadmin();

        $user = session('user');
        $absensi = (object) [
            'jadwal_id' => $request->get('jadwal_id'),
            'siswa_id' => $request->get('siswa_id'),
            'tanggal' => $request->get('tanggal', now()->toDateString()),
            'jam_scan' => null,
            'status' => $request->get('status', 'hadir'),
        ];
        $mode = 'create';
        $siswa = siswaAktifQuery()->orderBy('nama')->get();
        $jadwal = DB::table('jadwal_pelajarans as jp')
            ->join('kelas as k', 'k.id', '=', 'jp.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->select('jp.*', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as nama_guru')
            ->orderBy('k.nama_kelas')->orderBy('jp.hari')->orderBy('jp.jam_mulai')
            ->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = tahunAjaranAktifId();

        return view('dashboard.absensi_mapel_admin.form', compact('user', 'absensi', 'mode', 'siswa', 'jadwal', 'tahunAjaran', 'tahunAjaranId'));
    }

    public function mapelStore(Request $request)
    {
        wajibSuperadmin();

        $request->validate([
            'jadwal_id' => 'required|exists:jadwal_pelajarans,id',
            'siswa_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'jam_scan' => 'nullable',
            'status' => 'required|string|max:30',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        ]);

        $jadwal = DB::table('jadwal_pelajarans')->where('id', $request->jadwal_id)->first();
        $siswa = siswaAktifQuery()->where('id', $request->siswa_id)->first();

        if (! $jadwal || ! $siswa || (int) $siswa->kelas_id !== (int) $jadwal->kelas_id) {
            return back()->withInput()->with('error', 'Siswa harus sesuai dengan kelas pada jadwal mapel.');
        }

        $exists = DB::table('absensi_mapels')
            ->where('jadwal_id', $request->jadwal_id)
            ->where('siswa_id', $request->siswa_id)
            ->whereDate('tanggal', $request->tanggal)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Absensi mapel siswa pada jadwal dan tanggal ini sudah ada. Silakan edit data yang sudah ada.');
        }

        $teacherPayload = app(SubjectAttendanceTeacherService::class)->payload($jadwal, $request->tanggal);

        $payload = [
            'jadwal_id' => $request->jadwal_id,
            'siswa_id' => $request->siswa_id,
            'tanggal' => $request->tanggal,
            'jam_scan' => $request->jam_scan ?: null,
            'status' => $request->status,
            'created_at' => now(),
            'updated_at' => now(),
        ] + $teacherPayload;

        if (Schema::hasColumn('absensi_mapels', 'tahun_ajaran_id')) {
            $payload['tahun_ajaran_id'] = $request->tahun_ajaran_id ?: ($jadwal->tahun_ajaran_id ?? tahunAjaranAktifId());
        }

        $id = DB::table('absensi_mapels')->insertGetId($payload);
        app(AttendanceAuditService::class)->record('create', 'absensi_mapels', $id, null, $payload, $request, $request->input('alasan'));

        return redirect('/dashboard/admin/absensi-mapel')->with('success', 'Absensi mapel berhasil ditambahkan.');
    }

    public function mapelShow($id)
    {
        wajibSuperadmin();

        $user = session('user');
        $absensi = DB::table('absensi_mapels as a')
            ->leftJoin('users as s', 's.id', '=', 'a.siswa_id')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jadwal_pelajarans as jp', 'jp.id', '=', 'a.jadwal_id')
            ->leftJoin('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->leftJoin('users as g', 'g.id', '=', 'jp.guru_id')
            ->leftJoin('users as gpel', 'gpel.id', '=', 'a.guru_pelaksana_id')
            ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'a.tahun_ajaran_id')
            ->select('a.*', 's.nama as nama_siswa', 's.nis', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as guru_utama', 'gpel.nama as guru_pelaksana', 'jp.hari', 'jp.jam_mulai', 'jp.jam_selesai', 'jp.jam_ke_mulai', 'jp.jumlah_jp', 'ta.nama as tahun_ajaran', 'ta.semester')
            ->where('a.id', $id)->whereNull('a.deleted_at')
            ->first();

        abort_if(! $absensi, 404);

        return view('dashboard.absensi_mapel_admin.view', compact('user', 'absensi'));
    }

    public function mapelEdit($id)
    {
        wajibSuperadmin();

        $user = session('user');
        $absensi = DB::table('absensi_mapels')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(! $absensi, 404);

        $mode = 'edit';
        $siswa = siswaAktifQuery()->orderBy('nama')->get();
        $jadwal = DB::table('jadwal_pelajarans as jp')
            ->join('kelas as k', 'k.id', '=', 'jp.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->select('jp.*', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as nama_guru')
            ->orderBy('k.nama_kelas')->orderBy('jp.hari')->orderBy('jp.jam_mulai')
            ->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $absensi->tahun_ajaran_id ?? tahunAjaranAktifId();

        return view('dashboard.absensi_mapel_admin.form', compact('user', 'absensi', 'mode', 'siswa', 'jadwal', 'tahunAjaran', 'tahunAjaranId'));
    }

    public function mapelUpdate(Request $request, $id)
    {
        wajibSuperadmin();

        $request->validate([
            'jadwal_id' => 'required|exists:jadwal_pelajarans,id',
            'siswa_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'jam_scan' => 'nullable',
            'status' => 'required|string|max:30',
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
        ]);

        $old = DB::table('absensi_mapels')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(! $old, 404);

        $jadwal = DB::table('jadwal_pelajarans')->where('id', $request->jadwal_id)->first();
        $siswa = siswaAktifQuery()->where('id', $request->siswa_id)->first();

        if (! $jadwal || ! $siswa || (int) $siswa->kelas_id !== (int) $jadwal->kelas_id) {
            return back()->withInput()->with('error', 'Siswa harus sesuai dengan kelas pada jadwal mapel.');
        }

        $duplicate = DB::table('absensi_mapels')
            ->where('id', '!=', $id)
            ->where('jadwal_id', $request->jadwal_id)
            ->where('siswa_id', $request->siswa_id)
            ->whereDate('tanggal', $request->tanggal)
            ->whereNull('deleted_at')
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Data ganda ditolak. Siswa ini sudah punya absensi mapel pada jadwal dan tanggal tersebut.');
        }

        $teacherPayload = app(SubjectAttendanceTeacherService::class)->payload($jadwal, $request->tanggal);

        $payload = [
            'jadwal_id' => $request->jadwal_id,
            'siswa_id' => $request->siswa_id,
            'tanggal' => $request->tanggal,
            'jam_scan' => $request->jam_scan ?: null,
            'status' => $request->status,
            'updated_at' => now(),
        ] + $teacherPayload;

        if (Schema::hasColumn('absensi_mapels', 'tahun_ajaran_id')) {
            $payload['tahun_ajaran_id'] = $request->tahun_ajaran_id ?: ($jadwal->tahun_ajaran_id ?? tahunAjaranAktifId());
        }

        DB::table('absensi_mapels')->where('id', $id)->update($payload);
        app(AttendanceAuditService::class)->record('update', 'absensi_mapels', (int) $id, $old, (object) array_merge((array) $old, $payload), $request, $request->input('alasan'));

        return redirect('/dashboard/admin/absensi-mapel')->with('success', 'Absensi mapel berhasil diperbarui.');
    }

    public function mapelDelete(Request $request, $id)
    {
        wajibSuperadmin();

        $old = DB::table('absensi_mapels')->where('id', $id)->first();
        abort_if(! $old, 404);

        if (! hapusDataAdmin('absensi_mapels', (int) $id, 'Absensi mapel', $request)) {
            return back()->with('error', 'Absensi mapel gagal dihapus atau data tidak ditemukan.');
        }

        return back()->with('success', 'Absensi mapel berhasil dipindahkan ke arsip.');
    }
}
