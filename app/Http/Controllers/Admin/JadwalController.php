<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JadwalController extends Controller
{
    public function index()
    {
        $user = session('user');

        $jadwal = tanpaArsip(DB::table('jadwal_pelajarans as j'), 'jadwal_pelajarans', 'j')
            ->join(
                'kelas as k',
                'k.id',
                '=',
                'j.kelas_id'
            )
            ->join(
                'mapels as m',
                'm.id',
                '=',
                'j.mapel_id'
            )
            ->join(
                'users as g',
                'g.id',
                '=',
                'j.guru_id'
            )
            ->leftJoin(
                'users as gp',
                'gp.id',
                '=',
                'j.guru_pengganti_id'
            )
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as nama_guru',
                'gp.nama as nama_guru_pengganti',
                'j.keterangan',
                'j.status_guru'
            )
            ->orderBy(
                'j.hari'
            )
            ->orderBy(
                'j.jam_mulai'
            )
            ->get();

        return view(
            'dashboard.jadwal.index',
            compact(
                'user',
                'jadwal'
            )
        );
    }

    public function create()
    {
        $user = session('user');

        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();

        $mapels = DB::table('mapels')->orderBy('nama_mapel')->get();

        $guru = User::where('role', 'guru')
            ->orderBy('nama')
            ->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

        return view('dashboard.jadwal.create', compact(
            'user',
            'kelas',
            'mapels',
            'guru',
            'tahunAjaran',
            'tahunAjaranAktif'
        ));
    }

    public function bentrok(Request $request)
    {
        $user = session('user');
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranId = $request->get('tahun_ajaran_id') ?: tahunAjaranAktifId();
        $bentrok = collect();

        $jadwal = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'j.tahun_ajaran_id')
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('j.tahun_ajaran_id', $tahunAjaranId))
            ->select('j.*', 'k.nama_kelas', 'm.nama_mapel', 'g.nama as nama_guru', 'ta.nama as tahun_nama', 'ta.semester')
            ->get();

        foreach ($jadwal as $index => $a) {
            foreach ($jadwal->slice($index + 1) as $b) {
                if (strtolower($a->hari) !== strtolower($b->hari) || $a->tahun_ajaran_id != $b->tahun_ajaran_id) {
                    continue;
                }

                $overlap = $a->jam_mulai < $b->jam_selesai && $a->jam_selesai > $b->jam_mulai;

                if (! $overlap) {
                    continue;
                }

                if ($a->kelas_id == $b->kelas_id || $a->guru_id == $b->guru_id) {
                    $bentrok->push([
                        'tipe' => $a->kelas_id == $b->kelas_id ? 'Kelas' : 'Guru',
                        'tahun_ajaran' => ($a->tahun_nama ?: '-').' '.($a->semester ? ucfirst($a->semester) : ''),
                        'hari' => $a->hari,
                        'jam' => $a->jam_mulai.'-'.$a->jam_selesai.' bentrok '.$b->jam_mulai.'-'.$b->jam_selesai,
                        'detail_1' => $a->nama_kelas.' | '.$a->nama_mapel.' | '.$a->nama_guru,
                        'detail_2' => $b->nama_kelas.' | '.$b->nama_mapel.' | '.$b->nama_guru,
                    ]);
                }
            }
        }

        $piket = DB::table('guru_pikets as p')
            ->join('users as g', 'g.id', '=', 'p.guru_id')
            ->leftJoin('tahun_ajarans as ta', 'ta.id', '=', 'p.tahun_ajaran_id')
            ->when($tahunAjaranId && Schema::hasColumn('guru_pikets', 'tahun_ajaran_id'), fn ($query) => $query->where('p.tahun_ajaran_id', $tahunAjaranId))
            ->select('p.*', 'g.nama as nama_guru', 'ta.nama as tahun_nama', 'ta.semester')
            ->get();

        foreach ($piket as $p) {
            foreach ($jadwal as $j) {
                if (strtolower($p->hari) !== strtolower($j->hari) || $p->tahun_ajaran_id != $j->tahun_ajaran_id) {
                    continue;
                }

                $overlap = $p->jam_mulai < $j->jam_selesai && $p->jam_selesai > $j->jam_mulai;

                if ($overlap && in_array($p->guru_id, [$j->guru_id, $j->guru_pengganti_id])) {
                    $bentrok->push([
                        'tipe' => 'Piket vs Mengajar',
                        'tahun_ajaran' => ($p->tahun_nama ?: '-').' '.($p->semester ? ucfirst($p->semester) : ''),
                        'hari' => $p->hari,
                        'jam' => $p->jam_mulai.'-'.$p->jam_selesai.' bentrok '.$j->jam_mulai.'-'.$j->jam_selesai,
                        'detail_1' => 'Piket: '.$p->nama_guru,
                        'detail_2' => 'Mengajar: '.$j->nama_guru.' | '.$j->nama_kelas.' | '.$j->nama_mapel,
                    ]);
                }
            }
        }

        return view('dashboard.jadwal.bentrok', compact('user', 'tahunAjaran', 'tahunAjaranId', 'bentrok'));
    }

    public function edit($id)
    {
        $user = session('user');

        $jadwal = DB::table('jadwal_pelajarans')
            ->where('id', $id)
            ->first();

        if (! $jadwal) {
            abort(404);
        }

        $kelas = DB::table('kelas')->orderBy('nama_kelas')->get();
        $mapels = DB::table('mapels')->orderBy('nama_mapel')->get();
        $guru = User::where('role', 'guru')->orderBy('nama')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

        return view('dashboard.jadwal.edit', compact(
            'user',
            'jadwal',
            'kelas',
            'mapels',
            'guru',
            'tahunAjaran',
            'tahunAjaranAktif'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'kelas_id' => 'required',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'mapel_id' => 'required',
            'guru_id' => 'required',
            'guru_pengganti_id' => 'nullable',
            'keterangan' => 'nullable',
        ]);

        if ($pesanBentrok = validasiBentrokJadwalPelajaran($request)) {
            return back()
                ->withInput()
                ->with('error', $pesanBentrok);
        }

        if ($pesanLibur = validasiJadwalSaatLibur($request)) {
            return back()
                ->withInput()
                ->with('error', $pesanLibur);
        }

        $newId = DB::table(
            'jadwal_pelajarans'
        )
            ->insertGetId([
                'kelas_id' => $request->kelas_id,
                'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),
                'hari' => $request->hari,
                'jam_mulai' => $request->jam_mulai,
                'jam_selesai' => $request->jam_selesai,
                'mapel_id' => $request->mapel_id,
                'guru_id' => $request->guru_id,
                'guru_pengganti_id' => $request->guru_pengganti_id
                    ??
                    null,
                'status_guru' => null,
                'alasan_tidak_hadir' => null,
                'keterangan' => $request->keterangan
                    ??
                    null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        AuditLogger::record('create', 'jadwal_pelajarans', (int) $newId, 'Jadwal pelajaran ditambahkan', null, DB::table('jadwal_pelajarans')->where('id', $newId)->first(), $request);

        return redirect(
            '/dashboard/admin/jadwal'
        )
            ->with(
                'success',
                'Jadwal berhasil ditambahkan'
            );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'kelas_id' => 'required',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'mapel_id' => 'required',
            'guru_id' => 'required',
            'guru_pengganti_id' => 'nullable',
            'keterangan' => 'nullable',
        ]);

        if ($pesanBentrok = validasiBentrokJadwalPelajaran($request, (int) $id)) {
            return back()
                ->withInput()
                ->with('error', $pesanBentrok);
        }

        if ($pesanLibur = validasiJadwalSaatLibur($request)) {
            return back()
                ->withInput()
                ->with('error', $pesanLibur);
        }

        $before = DB::table('jadwal_pelajarans')->where('id', $id)->first();

        DB::table('jadwal_pelajarans')
            ->where('id', $id)
            ->update([
                'kelas_id' => $request->kelas_id,
                'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),
                'hari' => $request->hari,
                'jam_mulai' => $request->jam_mulai,
                'jam_selesai' => $request->jam_selesai,
                'mapel_id' => $request->mapel_id,
                'guru_id' => $request->guru_id,
                'guru_pengganti_id' => $request->guru_pengganti_id ?: null,
                'keterangan' => $request->keterangan ?: null,
                'updated_at' => now(),
            ]);
        AuditLogger::record('update', 'jadwal_pelajarans', (int) $id, 'Jadwal pelajaran diupdate', $before, DB::table('jadwal_pelajarans')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/jadwal')
            ->with('success', 'Jadwal berhasil diupdate');
    }

    public function delete($id)
    {
        arsipkanData('jadwal_pelajarans', (int) $id, 'Jadwal pelajaran', request());

        return redirect('/dashboard/admin/jadwal');
    }
}
