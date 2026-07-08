<?php

namespace App\Actions;

use App\Services\AttendanceAuditService;
use App\Services\DutyTeacherAssignmentService;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResetSubjectTeacherVerificationAction
{
    public function __construct(
        private readonly AttendanceAuditService $audit,
        private readonly DutyTeacherAssignmentService $assignments
    ) {}

    public function execute(int $attendanceId, int $adminId, string $reason, ?Request $request = null): object
    {
        return DB::transaction(function () use ($attendanceId, $adminId, $reason, $request) {
            $status = DB::table('jadwal_guru_statuses')->where('id', $attendanceId)->lockForUpdate()->first();
            if (! $status) {
                throw new HttpException(404, 'Data kehadiran guru mata pelajaran tidak ditemukan.');
            }
            if (! $status->status_dipilih_at) {
                throw new HttpException(422, 'Status guru mata pelajaran sudah belum terverifikasi.');
            }

            $schedule = DB::table('jadwal_pelajarans')->where('id', $status->jadwal_id)->lockForUpdate()->first();
            if (! $schedule || $schedule->deleted_at) {
                throw new HttpException(404, 'Jadwal mata pelajaran sudah tidak tersedia.');
            }

            $now = now('Asia/Jakarta');
            $wasAfterCutoff = $this->assignments->isPastCutoff($now);
            $oldStatus = $status->status_guru;
            $oldPickedAt = $status->status_dipilih_at;

            $replacements = DB::table('jadwal_guru_replacements')
                ->where('jadwal_id', $status->jadwal_id)
                ->whereDate('tanggal', $status->tanggal)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get();

            $replacementIds = $replacements->pluck('id')->map(fn ($id) => (int) $id)->all();
            $replacementTeacherIds = $replacements->pluck('guru_pengganti_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

            if ($replacementIds) {
                DB::table('jadwal_guru_replacements')->whereIn('id', $replacementIds)->update([
                    'status_penugasan' => 'dibatalkan',
                    'selesai_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('jadwal_guru_statuses')->where('id', $status->id)->update([
                'status_guru' => 'normal',
                'alasan_tidak_hadir' => null,
                'guru_pengganti_id' => null,
                'status_dipilih_at' => null,
                'pengganti_status' => null,
                'pengganti_alasan' => null,
                'pengganti_dipilih_at' => null,
                'updated_at' => $now,
            ]);

            $this->deactivateSubjectQr((int) $status->jadwal_id, (string) $status->tanggal, $now);

            $after = DB::table('jadwal_guru_statuses')->where('id', $status->id)->first();
            $this->audit->record(
                'cancel_subject_teacher_verification',
                'jadwal_guru_statuses',
                (int) $status->id,
                ['status' => $status, 'replacements' => $replacements],
                [
                    'status' => $after,
                    'status_lama' => $oldStatus,
                    'waktu_verifikasi_lama' => $oldPickedAt,
                    'status_baru' => 'belum_terverifikasi',
                    'reset_oleh' => $adminId,
                    'reset_at' => $now->toDateTimeString(),
                    'setelah_cutoff' => $wasAfterCutoff,
                    'replacement_ids_dinonaktifkan' => $replacementIds,
                    'replacement_teacher_ids_dinonaktifkan' => $replacementTeacherIds,
                    'jadwal_id' => $status->jadwal_id,
                    'tanggal' => $status->tanggal,
                ],
                $request,
                $reason
            );

            $this->notifyTeachers($schedule, (string) $status->tanggal, $replacementTeacherIds);

            return (object) [
                'status' => $after,
                'old_status' => $oldStatus,
                'old_confirmation_time' => $oldPickedAt,
                'disabled_replacements' => $replacements,
                'after_cutoff' => $wasAfterCutoff,
            ];
        });
    }

    private function deactivateSubjectQr(int $scheduleId, string $date, CarbonInterface $now): void
    {
        if (! Schema::hasTable('qr_sesis')) {
            return;
        }

        DB::table('qr_sesis')
            ->where('jadwal_id', $scheduleId)
            ->whereDate('tanggal', $date)
            ->where('aktif', true)
            ->update(['aktif' => false, 'updated_at' => $now]);
    }

    private function notifyTeachers(object $schedule, string $date, array $replacementTeacherIds): void
    {
        if (! function_exists('buatNotifikasi')) {
            return;
        }

        buatNotifikasi([
            'user_id' => $schedule->guru_id,
            'judul' => 'Verifikasi Mengajar Dibatalkan',
            'pesan' => 'Verifikasi kehadiran Anda untuk tanggal '.$date.' telah dibatalkan oleh admin. Silakan lakukan verifikasi ulang.',
            'kategori' => 'reset_verifikasi_mapel',
            'severity' => 'warning',
            'source_type' => 'jadwal_pelajarans',
            'source_id' => $schedule->id,
            'payload' => ['tanggal' => $date],
        ]);

        foreach ($replacementTeacherIds as $teacherId) {
            buatNotifikasi([
                'user_id' => $teacherId,
                'judul' => 'Penugasan Pengganti Dibatalkan',
                'pesan' => 'Penugasan Anda sebagai guru pengganti pada tanggal '.$date.' telah dibatalkan karena verifikasi guru utama direset oleh admin.',
                'kategori' => 'reset_pengganti_mapel',
                'severity' => 'info',
                'source_type' => 'jadwal_guru_replacements',
                'source_id' => $schedule->id,
                'payload' => ['tanggal' => $date],
            ]);
        }
    }
}
