<?php

namespace App\Http\Controllers\Qr;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrViewController extends Controller
{
    public function piketQrHarian(Request $request)
    {
        return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'qr'])));
    }

    public function piketView($id)
    {
        $user = session('user');
        $qr = QrCode::findOrFail($id);

        if ($qr->tanggal !== now()->toDateString()) {
            abort(403, 'QR ini bukan QR hari ini.');
        }

        $teamIds = collect(explode(',', (string) ($qr->guru_piket_ids ?? '')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $anggotaTim = $teamIds->isNotEmpty()
            ? DB::table('guru_pikets as gp')
                ->join('users as u', 'u.id', '=', 'gp.guru_id')
                ->leftJoin('users as g1', 'g1.id', '=', 'gp.guru_pengganti_id')
                ->leftJoin('users as g2', 'g2.id', '=', 'gp.guru_pengganti2_id')
                ->whereIn('gp.id', $teamIds)
                ->select('gp.*', 'u.nama as guru_utama', 'g1.nama as guru_pengganti', 'g2.nama as guru_pengganti2')
                ->orderBy('u.nama')
                ->get()
            : collect();

        $bolehLihat = ($user->role ?? null) === 'piket';

        if (($user->role ?? null) === 'guru') {
            $bolehLihat = $anggotaTim->contains(fn ($anggota) => (int) $anggota->guru_id === (int) $user->id
                || (int) $anggota->guru_pengganti_id === (int) $user->id
                || (int) $anggota->guru_pengganti2_id === (int) $user->id);
        }

        abort_if(! $bolehLihat, 403);

        $pembuatQr = $qr->generated_by
            ? DB::table('users')->where('id', $qr->generated_by)->value('nama')
            : null;

        $penggantiTim = $anggotaTim
            ->flatMap(fn ($anggota) => [$anggota->guru_pengganti, $anggota->guru_pengganti2])
            ->filter()
            ->unique()
            ->values();

        return view('dashboard.piket_qr_view', compact('user', 'qr', 'anggotaTim', 'pembuatQr', 'penggantiTim'));
    }

    public function guruView($id)
    {
        $user = session('user');

        $qr = DB::table('qr_sesis')->where('id', $id)->first();
        abort_if(! $qr, 404);

        if ($qr->tanggal !== now()->toDateString()) {
            abort(403, 'QR ini bukan QR hari ini.');
        }

        $detail = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as g', 'g.id', '=', 'j.guru_id')
            ->leftJoin('users as pg', 'pg.id', '=', 'j.guru_pengganti_id')
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as nama_guru',
                'pg.nama as nama_guru_pengganti'
            )
            ->where('j.id', $qr->jadwal_id)
            ->first();

        abort_if(! $detail, 404);

        $bolehLihat = (int) $detail->guru_id === (int) $user->id
            || ((int) ($detail->guru_pengganti_id ?? 0) === (int) $user->id && $detail->status_guru === 'digantikan');

        abort_if(! $bolehLihat, 403);

        return view('dashboard.guru_qr_view', compact('user', 'qr', 'detail'));
    }
}
