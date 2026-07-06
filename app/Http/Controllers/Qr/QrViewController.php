<?php

namespace App\Http\Controllers\Qr;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Services\ActiveDutyTeacherResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrViewController extends Controller
{
    private function userGuruBertugas($jadwal, int $userId): bool
    {
        $statusGuru = $jadwal->status_guru ?: 'normal';

        if ($statusGuru === 'normal') {
            return (int) $jadwal->guru_id === $userId;
        }

        return (int) ($jadwal->guru_pengganti_id ?? 0) === $userId
            && ($jadwal->pengganti_status ?? null) === 'bertugas';
    }

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
        if (isset($qr->aktif) && ! $qr->aktif) {
            abort(403, 'QR ini sudah tidak aktif karena penugasan guru piket telah berubah.');
        }

        $teamIds = collect(explode(',', (string) ($qr->guru_piket_ids ?? '')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $anggotaTim = $teamIds->isNotEmpty()
            ? DB::table('guru_pikets as gp')
                ->join('users as u', 'u.id', '=', 'gp.guru_id')
                ->whereIn('gp.id', $teamIds)
                ->select('gp.*', 'u.nama as guru_utama')
                ->orderBy('u.nama')
                ->get()
            : collect();

        $bolehLihat = ($user->role ?? null) === 'piket';

        if (($user->role ?? null) === 'guru') {
            $assignment = app(ActiveDutyTeacherResolver::class)->resolve($user, now('Asia/Jakarta')->toDateString());
            $bolehLihat = $assignment && $assignment->can_manage_qr
                && (! $qr->guru_piket_id || (int) $assignment->schedule->id === (int) $qr->guru_piket_id)
                && (! $qr->active_teacher_id || (int) $qr->active_teacher_id === (int) $user->id);
        }

        abort_if(! $bolehLihat, 403);

        $pembuatQr = $qr->generated_by
            ? DB::table('users')->where('id', $qr->generated_by)->value('nama')
            : null;

        return view('dashboard.piket_qr_view', compact('user', 'qr', 'anggotaTim', 'pembuatQr'));
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
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($qr) {
                $join->on('jgs.jadwal_id', '=', 'j.id')
                    ->whereDate('jgs.tanggal', $qr->tanggal);
            })
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'g.nama as nama_guru',
                DB::raw("COALESCE(jgs.status_guru, 'normal') as status_guru"),
                'jgs.alasan_tidak_hadir',
                'jgs.status_dipilih_at',
                'jgs.pengganti_status',
                'jgs.pengganti_alasan',
                'jgs.pengganti_dipilih_at'
            )
            ->where('j.id', $qr->jadwal_id)
            ->first();

        abort_if(! $detail, 404);

        $bolehLihat = $this->userGuruBertugas($detail, (int) $user->id);

        abort_if(! $bolehLihat, 403);

        return view('dashboard.guru_qr_view', compact('user', 'qr', 'detail'));
    }
}
