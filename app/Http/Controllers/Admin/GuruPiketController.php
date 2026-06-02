<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuruPiketController extends Controller
{
    public function index(Request $request)
    {
        $user = session('user');
        $hari = $request->hari;

        $query = tanpaArsip(DB::table('guru_pikets as gp'), 'guru_pikets', 'gp')
            ->join('users as u', 'u.id', '=', 'gp.guru_id')
            ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti_id')
            ->leftJoin('users as g3', 'g3.id', '=', 'gp.guru_pengganti2_id')
            ->select('gp.*', 'u.nama', 'g2.nama as guru_pengganti', 'g3.nama as guru_pengganti2');

        if (! empty($hari)) {
            $query->where('gp.hari', strtolower($hari));
        }

        $guruPiket = $query
            ->orderBy('gp.hari')
            ->orderBy('u.nama')
            ->get();

        $hariSekarang = strtolower(now()->locale('id')->translatedFormat('l'));
        $jamSekarang = now()->format('H:i:s');

        foreach ($guruPiket as $g) {
            if ($g->hari == $hariSekarang && ! in_array($g->status, ['Izin', 'Sakit', 'Digantikan'])) {
                if ($jamSekarang < $g->jam_mulai) {
                    $status = 'Akan Bertugas';
                } elseif ($jamSekarang >= $g->jam_mulai && $jamSekarang <= $g->jam_selesai) {
                    $status = 'Sedang Bertugas';
                } else {
                    $status = 'Selesai';
                }

                DB::table('guru_pikets')
                    ->where('id', $g->id)
                    ->update([
                        'status' => $status,
                        'updated_at' => now(),
                    ]);

                $g->status = $status;
            } elseif ($g->hari != $hariSekarang && ! in_array($g->status, ['Izin', 'Sakit', 'Digantikan'])) {
                DB::table('guru_pikets')
                    ->where('id', $g->id)
                    ->update([
                        'status' => 'Akan Bertugas',
                        'updated_at' => now(),
                    ]);

                $g->status = 'Akan Bertugas';
            }
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
            'Butuh Pengganti',
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
                } elseif ($statusAnggota->intersect(['Izin', 'Sakit', 'Digantikan'])->isNotEmpty()) {
                    $statusTim = 'Butuh Pengganti';
                } elseif ($statusAnggota->every(fn ($status) => $status === 'Selesai')) {
                    $statusTim = 'Selesai';
                } elseif ($statusAnggota->contains('Akan Bertugas')) {
                    $statusTim = 'Akan Bertugas';
                } else {
                    $statusTim = $statusAnggota->first() ?: '-';
                }

                $statusClass = match ($statusTim) {
                    'Sedang Bertugas' => 'sedang',
                    'Butuh Pengganti' => 'ganti',
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

        return view('dashboard.guru_piket.index', compact('user', 'guruPiket', 'timPiket', 'ringkasanPiket', 'hari'));
    }

    public function create()
    {
        $user = session('user');

        $guru = User::where('role', 'guru')->orderBy('nama')->get();
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

        $guru = User::where('role', 'guru')->orderBy('nama')->get();
        $tahunAjaran = DB::table('tahun_ajarans')->orderByDesc('tanggal_mulai')->get();
        $tahunAjaranAktif = DB::table('tahun_ajarans')->where('aktif', true)->first();

        return view('dashboard.guru_piket.edit', compact('user', 'guruPiket', 'guru', 'tahunAjaran', 'tahunAjaranAktif'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'guru_id' => 'required|array',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
        ]);

        if (count($request->guru_id) < 5) {
            return back()->with('error', 'Minimal 5 guru piket');
        }

        if ($pesanBentrok = validasiBentrokGuruPiket($request)) {
            return back()
                ->withInput()
                ->with('error', $pesanBentrok);
        }

        foreach ($request->guru_id as $guruId) {
            $cek = DB::table('guru_pikets')
                ->where('guru_id', $guruId)
                ->where('hari', strtolower($request->hari))
                ->where('jam_mulai', '<', $request->jam_selesai)
                ->where('jam_selesai', '>', $request->jam_mulai)
                ->exists();

            if ($cek) {
                continue;
            }

            $newId = DB::table('guru_pikets')
                ->insertGetId([
                    'guru_id' => $guruId,
                    'tahun_ajaran_id' => $request->tahun_ajaran_id ?: tahunAjaranAktifId(),
                    'guru_pengganti_id' => $request->guru_pengganti_id ?? null,
                    'guru_pengganti2_id' => $request->guru_pengganti2_id ?? null,
                    'hari' => strtolower($request->hari),
                    'jam_mulai' => $request->jam_mulai,
                    'jam_selesai' => $request->jam_selesai,
                    'status' => 'Akan Bertugas',
                    'aktif' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            AuditLogger::record('create', 'guru_pikets', (int) $newId, 'Guru piket ditambahkan', null, DB::table('guru_pikets')->where('id', $newId)->first(), $request);
        }

        return redirect('/dashboard/admin/guru-piket')
            ->with('success', 'Guru piket berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tahun_ajaran_id' => 'nullable|exists:tahun_ajarans,id',
            'guru_id' => 'required',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'guru_pengganti_id' => 'nullable',
            'guru_pengganti2_id' => 'nullable',
            'status' => 'required|in:Akan Bertugas,Sedang Bertugas,Izin,Sakit,Digantikan,Selesai',
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
                'guru_pengganti_id' => $request->guru_pengganti_id ?: null,
                'guru_pengganti2_id' => $request->guru_pengganti2_id ?: null,
                'hari' => strtolower($request->hari),
                'jam_mulai' => $request->jam_mulai,
                'jam_selesai' => $request->jam_selesai,
                'status' => $request->status,
                'aktif' => $request->has('aktif') ? 1 : 0,
                'updated_at' => now(),
            ]);
        AuditLogger::record('update', 'guru_pikets', (int) $id, 'Guru piket diupdate', $before, DB::table('guru_pikets')->where('id', $id)->first(), $request);

        return redirect('/dashboard/admin/guru-piket')
            ->with('success', 'Guru piket berhasil diupdate');
    }

    public function delete($id)
    {
        arsipkanData('guru_pikets', (int) $id, 'Guru piket', request());

        return redirect('/dashboard/admin/guru-piket')
            ->with('success', 'Guru piket berhasil dihapus');
    }
}
