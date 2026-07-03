<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DutyTeacherAttendanceService;
use App\Services\DutyTeacherAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Controller ini mengelola jadwal guru piket dan pengganti guru piket.
class GuruPiketController extends Controller
{
    // Menampilkan daftar guru piket, status harian, dan rantai pengganti pada tanggal tertentu.
    public function index(Request $request, DutyTeacherAttendanceService $attendance)
    {
        $user = session('user');
        $hari = $request->hari;
        $tanggal = $request->get('tanggal', now('Asia/Jakarta')->toDateString());

        // Query ini mengambil jadwal piket aktif beserta nama guru utama dan guru pengganti.
        $query = tanpaArsip(DB::table('guru_pikets as gp'), 'guru_pikets', 'gp')
            ->join('users as u', 'u.id', '=', 'gp.guru_id')
            ->leftJoin('users as pg', 'pg.id', '=', 'gp.guru_pengganti_id')
            ->select('gp.*', 'u.nama', 'pg.nama as nama_pengganti');

        if (! empty($hari)) {
            $query->where('gp.hari', strtolower($hari));
        }

        $guruPiket = $query
            ->orderBy('gp.hari')
            ->orderBy('u.nama')
            ->get();

        // Status harian ditempelkan ke setiap jadwal agar admin tahu siapa yang sudah konfirmasi.
        $statusHarian = $attendance->statusesFor($guruPiket->pluck('id')->map(fn ($id) => (int) $id)->all(), $tanggal);
        foreach ($guruPiket as $g) {
            $status = $statusHarian->get($attendance->statusKey((int) $g->id, (int) $g->guru_id));
            $g->status_harian_raw = $status?->status;
            $g->status_harian = $status?->status;
            $g->waktu_konfirmasi = $status?->waktu_konfirmasi;
            $g->status_harian_label = match ($status?->status) {
                'hadir' => 'Hadir',
                'izin' => 'Izin',
                'sakit' => 'Sakit',
                'digantikan' => 'Digantikan',
                'selesai' => 'Selesai',
                default => 'Belum Konfirmasi',
            };
            $g->status = $attendance->labelFor($g, $tanggal);
        }

        // Rantai pengganti memperlihatkan urutan guru pengganti jika guru utama atau pengganti berhalangan.
        $replacementRows = DB::table('guru_piket_replacements as r')
            ->join('users as p', 'p.id', '=', 'r.guru_pengganti_id')
            ->leftJoin('users as a', 'a.id', '=', 'r.ditunjuk_oleh')
            ->leftJoin('guru_piket_statuses as s', function ($join) {
                $join->on('s.guru_piket_id', '=', 'r.guru_piket_id')->on('s.guru_id', '=', 'r.guru_pengganti_id')->on('s.tanggal', '=', 'r.tanggal')->whereNull('s.deleted_at');
            })->whereDate('r.tanggal', $tanggal)->whereNull('r.deleted_at')
            ->select('r.*', 'p.nama as nama_pengganti_rantai', 'a.nama as nama_admin', 's.status as status_kehadiran', 's.waktu_konfirmasi', 's.sumber')
            ->orderBy('r.urutan_penggantian')->get()->groupBy('guru_piket_id');
        foreach ($guruPiket as $g) {
            $g->replacement_chain = $replacementRows->get($g->id, collect());
            $state = $attendance->buildDutyState($g, $tanggal, $statusHarian, $g->replacement_chain);
            $g->needs_replacement = $g->replacement_chain->last()?->status_penugasan === 'berhalangan';
            $g->duty_state = $state;
            $g->status_harian = $state->primary_effective_status;
            $g->status_harian_label = $state->primary_status_label;
            $g->active_officer = $state->active_teacher_name;
            $g->active_officer_label = $state->active_label;
            $g->status = match (true) {
                in_array($state->primary_effective_status, ['hadir', 'hadir_otomatis'], true) && $state->active_role === 'utama' => 'Sedang Bertugas',
                $state->active_role !== null && $state->active_role !== 'utama' => 'Sedang Bertugas',
                in_array($state->primary_effective_status, ['izin', 'sakit'], true) => ucfirst($state->primary_effective_status),
                $state->primary_effective_status === 'selesai' => 'Selesai',
                default => 'Akan Bertugas',
            };
        }

        $urutanHari = [
            'senin' => 1,
            'selasa' => 2,
            'rabu' => 3,
            'kamis' => 4,
            'jumat' => 5,
            'sabtu' => 6,
            'minggu' => 7,
        ];

        $prioritasStatus = [
            'Sedang Bertugas',
            'Ada Yang Izin/Sakit',
            'Akan Bertugas',
            'Selesai',
        ];

        $timPiket = $guruPiket
            ->groupBy(function ($g) {
                return implode('|', [
                    $g->tahun_ajaran_id ?? 'aktif',
                    strtolower((string) $g->hari),
                    $g->jam_mulai ?: '-',
                    $g->jam_selesai ?: '-',
                ]);
            })
            ->map(function ($anggota) use ($urutanHari, $prioritasStatus) {
                $pertama = $anggota->first();
                $statusAnggota = $anggota->pluck('status');

                if ($statusAnggota->contains('Sedang Bertugas')) {
                    $statusTim = 'Sedang Bertugas';
                } elseif ($statusAnggota->intersect(['Izin', 'Sakit'])->isNotEmpty()) {
                    $statusTim = 'Ada Yang Izin/Sakit';
                } elseif ($statusAnggota->every(fn ($status) => $status === 'Selesai')) {
                    $statusTim = 'Selesai';
                } elseif ($statusAnggota->contains('Akan Bertugas')) {
                    $statusTim = 'Akan Bertugas';
                } else {
                    $statusTim = $statusAnggota->first() ?: '-';
                }

                $statusClass = match ($statusTim) {
                    'Sedang Bertugas' => 'sedang',
                    'Ada Yang Izin/Sakit' => 'ganti',
                    'Selesai' => 'selesai',
                    default => 'akan',
                };

                return (object) [
                    'hari' => strtolower((string) $pertama->hari),
                    'hari_label' => ucfirst((string) $pertama->hari),
                    'hari_order' => $urutanHari[strtolower((string) $pertama->hari)] ?? 99,
                    'jam_mulai' => $pertama->jam_mulai,
                    'jam_selesai' => $pertama->jam_selesai,
                    'tahun_ajaran_id' => $pertama->tahun_ajaran_id ?? null,
                    'anggota' => $anggota->values(),
                    'jumlah' => $anggota->count(),
                    'status' => collect($prioritasStatus)->first(fn ($status) => $status === $statusTim) ?: $statusTim,
                    'status_class' => $statusClass,
                ];
            })
            ->sortBy(fn ($tim) => sprintf('%02d-%s', $tim->hari_order, $tim->jam_mulai ?: '99:99:99'))
            ->values();

        $ringkasanPiket = [
            'tim' => $timPiket->count(),
            'guru' => $guruPiket->count(),
            'aktif' => $timPiket->where('status', 'Sedang Bertugas')->count(),
        ];

        return view('dashboard.guru_piket.index', compact('user', 'guruPiket', 'timPiket', 'ringkasanPiket', 'hari', 'tanggal'));
    }

    public function replacementForm(Request $request, int $id, DutyTeacherAssignmentService $assignments)
    {
        $tanggal = $request->get('tanggal', now('Asia/Jakarta')->toDateString());
        $jadwal = DB::table('guru_pikets')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(! $jadwal, 404);
        $chain = DB::table('guru_piket_replacements as r')->join('users as u', 'u.id', '=', 'r.guru_pengganti_id')
            ->where('r.guru_piket_id', $id)->whereDate('r.tanggal', $tanggal)->whereNull('r.deleted_at')
            ->select('r.*', 'u.nama')->orderBy('r.urutan_penggantian')->get();
        abort_unless($chain->last()?->status_penugasan === 'berhalangan', 422, 'Tim ini belum membutuhkan pengganti lanjutan.');
        $calon = $assignments->availableCandidates($jadwal, $tanggal);
        return view('dashboard.guru_piket.replacement', ['user' => session('user'), 'jadwal' => $jadwal, 'chain' => $chain, 'calon' => $calon, 'tanggal' => $tanggal]);
    }

    public function replacementStore(Request $request, int $id, DutyTeacherAssignmentService $assignments)
    {
        $request->validate(['tanggal' => 'required|date', 'guru_id' => 'required|integer|exists:users,id', 'alasan' => 'required|string|max:500', 'catatan' => 'nullable|string|max:500']);
        $jadwal = DB::table('guru_pikets')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(! $jadwal, 404);
        try {
            $assignments->assignContinuation($jadwal, $request->tanggal, (int) $request->guru_id, (int) session('user')->id, $request->alasan, $request->catatan);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withInput()->with('error', 'Penugasan yang sama sudah tersimpan. Muat ulang halaman.');
        }
        return redirect('/dashboard/admin/guru-piket?tanggal='.$request->tanggal)->with('success', 'Guru pengganti lanjutan berhasil ditunjuk.');
    }

    public function create()
    {
        $user = session('user');

        $guru = User::where('role', 'guru')->where('aktif', 1)->whereNull('deleted_at')->orderBy('nama')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

        return view('dashboard.guru_piket.create', compact('user', 'guru', 'tahunAjaran', 'tahunAjaranAktif'));
    }

    public function edit($id)
    {
        $user = session('user');

        $guruPiket = DB::table('guru_pikets')
            ->where('id', $id)
            ->first();

        if (! $guruPiket) {
            abort(404);
        }

        $guru = User::where('role', 'guru')->where('aktif', 1)->whereNull('deleted_at')->orderBy('nama')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

        return view('dashboard.guru_piket.edit', compact('user', 'guruPiket', 'guru', 'tahunAjaran', 'tahunAjaranAktif'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'guru_id' => 'required|array|size:3',
            'guru_pengganti_id' => 'required|array|size:3',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);

        $guruUtamaIds = collect($request->guru_id)->filter()->unique()->values();
        $guruPenggantiIds = collect($request->guru_pengganti_id)->filter()->unique()->values();

        if ($guruUtamaIds->count() < 3) {
            return back()->withInput()->with('error', 'Guru utama piket kurang dari 3. Pilih tepat 3 guru utama.');
        }

        if ($guruUtamaIds->count() > 3) {
            return back()->withInput()->with('error', 'Guru utama piket lebih dari 3. Pilih tepat 3 guru utama.');
        }

        if ($guruPenggantiIds->count() < 3) {
            return back()->withInput()->with('error', 'Guru pengganti piket kurang dari 3. Pilih tepat 3 guru pengganti.');
        }

        if ($guruPenggantiIds->count() > 3) {
            return back()->withInput()->with('error', 'Guru pengganti piket lebih dari 3. Pilih tepat 3 guru pengganti.');
        }

        if ($guruUtamaIds->intersect($guruPenggantiIds)->isNotEmpty()) {
            return back()->withInput()->with('error', 'Guru utama dan guru pengganti tidak boleh orang yang sama dalam satu tim.');
        }

        if ($pesanGuruNonaktif = validasiGuruAktifIds($guruUtamaIds->merge($guruPenggantiIds)->all())) {
            return back()
                ->withInput()
                ->with('error', $pesanGuruNonaktif);
        }

        if ($pesanBentrok = validasiBentrokGuruPiket($request)) {
            return back()
                ->withInput()
                ->with('error', $pesanBentrok);
        }

        $inserted = 0;
        $skipped = 0;

        foreach ($guruUtamaIds as $index => $guruId) {
            $guruPenggantiId = $guruPenggantiIds->get($index % $guruPenggantiIds->count());

            $cek = DB::table('guru_pikets')
                ->where('guru_id', $guruId)
                ->where('hari', strtolower($request->hari))
                ->where('jam_mulai', '<', $request->jam_selesai)
                ->where('jam_selesai', '>', $request->jam_mulai)
                ->exists();

            if ($cek) {
                $skipped++;
                continue;
            }

            $newId = DB::table('guru_pikets')
                ->insertGetId([
                    'guru_id' => $guruId,
                    'guru_pengganti_id' => $guruPenggantiId,
                    'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),
                    'hari' => strtolower($request->hari),
                    'jam_mulai' => $request->jam_mulai,
                    'jam_selesai' => $request->jam_selesai,
                    'status' => 'Akan Bertugas',
                    'aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $inserted++;
        }

        if ($inserted === 0) {
            return back()
                ->withInput()
                ->with('error', 'Guru piket tidak tersimpan karena semua guru utama sudah terdaftar atau bentrok pada hari dan jam tersebut.');
        }

        return redirect('/dashboard/admin/guru-piket')
            ->with('success', 'Guru piket berhasil ditambahkan'.($skipped > 0 ? '. '.$skipped.' guru dilewati karena sudah terdaftar pada jam tersebut.' : ''));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'guru_id' => 'required',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'status' => 'required|in:Akan Bertugas,Sedang Bertugas,Izin,Sakit,Selesai',
        ]);

        $cek = DB::table('guru_pikets')
            ->where('guru_id', $request->guru_id)
            ->where('hari', strtolower($request->hari))
            ->where('id', '!=', $id)
            ->where('jam_mulai', '<', $request->jam_selesai)
            ->where('jam_selesai', '>', $request->jam_mulai)
            ->exists();

        if ($cek) {
            return back()
                ->withInput()
                ->with('error', 'Guru tersebut sudah terdaftar sebagai guru piket pada jam yang sama.');
        }

        if ($pesanGuruNonaktif = validasiGuruAktifIds([$request->guru_id])) {
            return back()
                ->withInput()
                ->with('error', $pesanGuruNonaktif);
        }

        if ($pesanBentrok = validasiBentrokGuruPiket($request, (int) $id)) {
            return back()
                ->withInput()
                ->with('error', $pesanBentrok);
        }

        $before = DB::table('guru_pikets')->where('id', $id)->first();

        DB::table('guru_pikets')
            ->where('id', $id)
            ->update([
                'guru_id' => $request->guru_id,
                'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),
                'hari' => strtolower($request->hari),
                'jam_mulai' => $request->jam_mulai,
                'jam_selesai' => $request->jam_selesai,
                'status' => $request->status,
                'aktif' => $request->has('aktif') ? 1 : 0,
                'updated_at' => now(),
            ]);

        return redirect('/dashboard/admin/guru-piket')
            ->with('success', 'Guru piket berhasil diupdate');
    }

    public function delete($id)
    {
        if (! hapusDataAdmin('guru_pikets', (int) $id, 'Guru piket', request())) {
            return redirect('/dashboard/admin/guru-piket')
                ->with('error', 'Guru piket gagal dihapus atau data tidak ditemukan.');
        }

        return redirect('/dashboard/admin/guru-piket')
            ->with('success', 'Guru piket berhasil dipindahkan ke arsip');
    }

    public function deleteTeam($id)
    {
        $teamBase = DB::table('guru_pikets')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first();

        if (! $teamBase) {
            return redirect('/dashboard/admin/guru-piket')
                ->with('error', 'Tim guru piket tidak ditemukan.');
        }

        $query = DB::table('guru_pikets')
            ->where('hari', $teamBase->hari)
            ->where('jam_mulai', $teamBase->jam_mulai)
            ->where('jam_selesai', $teamBase->jam_selesai)
            ->whereNull('deleted_at');

        if ($teamBase->tahun_ajaran_id) {
            $query->where('tahun_ajaran_id', $teamBase->tahun_ajaran_id);
        } else {
            $query->whereNull('tahun_ajaran_id');
        }

        $deleted = $query->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect('/dashboard/admin/guru-piket')
            ->with('success', 'Tim guru piket berhasil dipindahkan ke arsip. Total anggota terarsip: '.$deleted.'.');
    }
}
