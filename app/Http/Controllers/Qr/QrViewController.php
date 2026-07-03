<?php

namespace App\Http\Controllers\Qr;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use App\Services\ActiveDutyTeacherResolver;
use App\Services\DutyTeacherAttendanceService;
use App\Services\SubjectAttendanceTeacherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrViewController extends Controller
{
    private function userGuruBertugas($jadwal, int $userId, string $tanggal): bool
    {
        // Guru hanya boleh melihat QR mapel jika dia guru pelaksana aktif pada jadwal tersebut.
        return app(SubjectAttendanceTeacherService::class)
            ->canManage($jadwal, $tanggal, $userId);
    }

    public function piketQrHarian(Request $request)
    {
        // Route ini mengarahkan guru piket ke halaman dashboard piket bagian QR.
        return redirect('/dashboard/piket?'.http_build_query(array_merge($request->query(), ['page' => 'qr'])));
    }

    public function piketView($id)
    {
        // Menampilkan QR harian yang dibuat oleh guru piket.
        $user = session('user');
        $qr = QrCode::findOrFail($id);

        // QR harian hanya boleh dilihat pada tanggal yang sama dengan tanggal QR.
        if ($qr->tanggal !== now()->toDateString()) {
            abort(403, 'QR ini bukan QR hari ini.');
        }

        // QR yang sudah tidak aktif tidak boleh ditampilkan lagi.
        if (isset($qr->aktif) && ! $qr->aktif) {
            abort(403, 'QR ini sudah tidak aktif karena penugasan guru piket telah berubah.');
        }

        // guru_piket_ids disimpan sebagai daftar ID tim piket, lalu diubah menjadi collection angka.
        $teamIds = collect(explode(',', (string) ($qr->guru_piket_ids ?? '')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        // Data anggota tim piket diambil untuk ditampilkan di halaman QR.
        $anggotaTim = $teamIds->isNotEmpty()
            ? DB::table('guru_pikets as gp')
                ->join('users as u', 'u.id', '=', 'gp.guru_id')
                ->whereIn('gp.id', $teamIds)
                ->select('gp.*', 'u.nama as guru_utama')
                ->orderBy('u.nama')
                ->get()
            : collect();

        if ($anggotaTim->isNotEmpty()) {
            $dutyState = app(DutyTeacherAttendanceService::class)->buildDutyState($anggotaTim->first(), now('Asia/Jakarta')->toDateString());
            $qrAvailability = app(DutyTeacherAttendanceService::class)->resolveQrAvailability($dutyState, $user, hariLiburSekolah(now()->toDateString()), now('Asia/Jakarta'));
            abort_if(! $qrAvailability->can_manage, 403, $qrAvailability->reason ?: 'QR harian tidak aktif.');
        }

        // Role piket boleh melihat QR harian secara langsung.
        $bolehLihat = ($user->role ?? null) === 'piket';

        // Guru biasa hanya boleh melihat QR jika ia sedang menjadi guru piket aktif/pengganti.
        if (($user->role ?? null) === 'guru') {
            $assignment = app(ActiveDutyTeacherResolver::class)->resolve($user, now('Asia/Jakarta')->toDateString());
            $bolehLihat = $assignment && $assignment->can_manage_qr
                && (! $qr->guru_piket_id || (int) $assignment->schedule->id === (int) $qr->guru_piket_id)
                && (! $qr->active_teacher_id || (int) $qr->active_teacher_id === (int) $user->id);
        }

        abort_if(! $bolehLihat, 403);

        // Nama pembuat QR ditampilkan agar jelas siapa yang membuat QR tersebut.
        $pembuatQr = $qr->generated_by
            ? DB::table('users')->where('id', $qr->generated_by)->value('nama')
            : null;

        return view('dashboard.piket_qr_view', compact('user', 'qr', 'anggotaTim', 'pembuatQr'));
    }

    public function guruView($id)
    {
        // Menampilkan QR mapel yang dibuat oleh guru pelaksana.
        $user = session('user');

        // QR mapel tersimpan pada tabel qr_sesis.
        $qr = DB::table('qr_sesis')->where('id', $id)->first();
        abort_if(! $qr, 404);

        // QR mapel juga hanya berlaku pada tanggal dibuat.
        if ($qr->tanggal !== now()->toDateString()) {
            abort(403, 'QR ini bukan QR hari ini.');
        }

        // Detail jadwal, kelas, mapel, guru utama, dan status guru diambil untuk halaman QR.
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

        // Guru yang melihat QR harus guru yang sedang bertugas pada jadwal tersebut.
        $bolehLihat = $this->userGuruBertugas($detail, (int) $user->id, $qr->tanggal);

        abort_if(! $bolehLihat, 403);

        return view('dashboard.guru_qr_view', compact('user', 'qr', 'detail'));
    }
}
