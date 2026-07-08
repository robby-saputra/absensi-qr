<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ResetDutyTeacherVerificationAction;
use App\Actions\ResetSubjectTeacherVerificationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelTeacherVerificationRequest;
use App\Services\ActiveTeachingTeacherResolver;
use App\Services\DutyTeacherAssignmentService;
use App\Services\DutyTeacherAttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TeacherVerificationMonitoringController extends Controller
{
    public function index(Request $request, DutyTeacherAttendanceService $dutyAttendance, ActiveTeachingTeacherResolver $teachingResolver, DutyTeacherAssignmentService $assignments)
    {
        $user = session('user');
        $tanggal = $request->get('tanggal', now('Asia/Jakarta')->toDateString());
        $tab = $request->get('tab', 'piket');
        $hari = $dutyAttendance->dayNameForDate($tanggal);
        $isAfterCutoff = $assignments->isPastCutoff(now('Asia/Jakarta'));

        $piketRows = DB::table('guru_pikets as gp')
            ->join('users as u', 'u.id', '=', 'gp.guru_id')
            ->leftJoin('guru_piket_statuses as gps', function ($join) use ($tanggal) {
                $join->on('gps.guru_piket_id', '=', 'gp.id')
                    ->on('gps.guru_id', '=', 'gp.guru_id')
                    ->whereDate('gps.tanggal', $tanggal)
                    ->whereNull('gps.deleted_at');
            })
            ->where('gp.hari', $hari)
            ->where('gp.aktif', 1)
            ->whereNull('gp.deleted_at')
            ->select(
                'gp.*',
                'u.nama as nama_guru',
                'gps.id as attendance_id',
                'gps.status as status_harian',
                'gps.waktu_konfirmasi',
                'gps.sumber'
            )
            ->orderBy('gp.jam_mulai')
            ->orderBy('u.nama')
            ->get();

        $piketChains = DB::table('guru_piket_replacements as r')
            ->join('users as u', 'u.id', '=', 'r.guru_pengganti_id')
            ->whereDate('r.tanggal', $tanggal)
            ->whereNull('r.deleted_at')
            ->select('r.*', 'u.nama as nama_pengganti')
            ->orderBy('r.urutan_penggantian')
            ->get()
            ->groupBy('guru_piket_id');

        foreach ($piketRows as $row) {
            $row->tanggal_tugas = $tanggal;
            $row->replacement_chain = $piketChains->get($row->id, collect());
            $state = $dutyAttendance->buildDutyState($row, $tanggal, null, $row->replacement_chain);
            $row->status_label = $dutyAttendance->statusLabel($row->status_harian, $row->sumber);
            $row->sudah_verifikasi = ! empty($row->waktu_konfirmasi) && $row->status_harian !== DutyTeacherAttendanceService::BELUM_KONFIRMASI;
            $row->active_officer_label = $state->active_label;
            $row->active_replacement = $row->replacement_chain->where('status_penugasan', 'aktif')->last();
            $row->continuations = $row->replacement_chain->where('urutan_penggantian', '>', 1)->values();
            $row->has_student_attendance = DB::table('absensis')->whereDate('tanggal', $tanggal)->whereNull('deleted_at')->exists();
        }

        $mapelRows = DB::table('jadwal_pelajarans as j')
            ->join('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->join('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->join('users as u', 'u.id', '=', 'j.guru_id')
            ->leftJoin('jadwal_guru_statuses as jgs', function ($join) use ($tanggal) {
                $join->on('jgs.jadwal_id', '=', 'j.id')->whereDate('jgs.tanggal', $tanggal);
            })
            ->whereRaw('LOWER(j.hari) = ?', [$hari])
            ->whereNull('j.deleted_at')
            ->select(
                'j.*',
                'k.nama_kelas',
                'm.nama_mapel',
                'u.nama as nama_guru',
                'jgs.id as attendance_id',
                'jgs.status_guru as status_harian',
                'jgs.status_dipilih_at',
                'jgs.alasan_tidak_hadir'
            )
            ->orderBy('j.jam_mulai')
            ->orderBy('k.nama_kelas')
            ->orderBy('m.nama_mapel')
            ->get();

        $mapelStates = $teachingResolver->resolveMany($mapelRows, $tanggal);
        $mapelChains = DB::table('jadwal_guru_replacements as r')
            ->join('users as u', 'u.id', '=', 'r.guru_pengganti_id')
            ->whereDate('r.tanggal', $tanggal)
            ->whereNull('r.deleted_at')
            ->select('r.*', 'u.nama as nama_pengganti')
            ->orderBy('r.urutan_penggantian')
            ->get()
            ->groupBy('jadwal_id');

        foreach ($mapelRows as $row) {
            $row->tanggal_tugas = $tanggal;
            $row->replacement_chain = $mapelChains->get($row->id, collect());
            $state = $mapelStates->get((int) $row->id);
            $row->raw_status = $state?->raw_status ?? ($row->status_dipilih_at ? ($row->status_harian ?: 'normal') : ActiveTeachingTeacherResolver::BELUM_KONFIRMASI);
            $row->effective_status = $state?->effective_status ?? $row->raw_status;
            $row->status_harian = $row->effective_status;
            $row->status_label = $state?->status_label ?? $teachingResolver->statusLabel($row->effective_status);
            $row->status_source = $state?->status_source;
            $row->is_manual = (bool) ($state?->is_manual ?? ! empty($row->status_dipilih_at));
            $row->is_automatic_cutoff = (bool) ($state?->is_automatic_cutoff ?? false);
            $row->requires_admin_attention = (bool) ($state?->requires_admin_attention ?? empty($row->status_dipilih_at));
            $row->sudah_verifikasi = ! $row->requires_admin_attention;
            $row->can_cancel_verification = ! empty($row->status_dipilih_at) && ! empty($row->attendance_id);
            $row->active_replacement = $state?->active_replacement;
            $row->active_teacher_name = $state?->active_teacher_name;
            $row->continuations = $row->replacement_chain->where('urutan_penggantian', '>', 1)->values();
            $row->has_student_attendance = DB::table('absensi_mapels')->where('jadwal_id', $row->id)->whereDate('tanggal', $tanggal)->whereNull('deleted_at')->exists();
        }

        return view('dashboard.admin_teacher_verifications.index', compact(
            'user',
            'tanggal',
            'tab',
            'hari',
            'isAfterCutoff',
            'piketRows',
            'mapelRows'
        ));
    }

    public function cancelDuty(CancelTeacherVerificationRequest $request, int $attendance, ResetDutyTeacherVerificationAction $action)
    {
        try {
            $result = $action->execute($attendance, (int) session('user')->id, $request->validated('alasan'), $request);
        } catch (HttpException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = 'Verifikasi guru piket berhasil dibatalkan. Guru utama harus melakukan verifikasi ulang.';
        if ($result->after_cutoff) {
            $message .= ' Reset ini tercatat sebagai override setelah cutoff.';
        }

        return back()->with('success', $message);
    }

    public function cancelSubject(CancelTeacherVerificationRequest $request, int $attendance, ResetSubjectTeacherVerificationAction $action)
    {
        try {
            $result = $action->execute($attendance, (int) session('user')->id, $request->validated('alasan'), $request);
        } catch (HttpException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $message = 'Verifikasi guru mata pelajaran berhasil dibatalkan. Guru utama harus melakukan verifikasi ulang.';
        if ($result->after_cutoff) {
            $message .= ' Reset ini tercatat sebagai override setelah cutoff.';
        }

        return back()->with('success', $message);
    }
}
