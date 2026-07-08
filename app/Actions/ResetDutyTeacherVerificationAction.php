<?php

namespace App\Actions;

use App\Models\GuruPiketStatus;
use App\Services\AttendanceAuditService;
use App\Services\DutyTeacherAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResetDutyTeacherVerificationAction
{
    public function __construct(
        private readonly AttendanceAuditService $audit,
        private readonly DutyTeacherAssignmentService $assignments
    ) {}

    public function execute(int $attendanceId, int $adminId, string $reason, ?Request $request = null): object
    {
        return DB::transaction(function () use ($attendanceId, $adminId, $reason, $request) {
            $status = GuruPiketStatus::query()->whereKey($attendanceId)->lockForUpdate()->first();
            if (! $status) {
                throw new HttpException(404, 'Data kehadiran guru piket tidak ditemukan.');
            }

            $schedule = DB::table('guru_pikets')->where('id', $status->guru_piket_id)->lockForUpdate()->first();
            if (! $schedule || $schedule->deleted_at) {
                throw new HttpException(404, 'Jadwal guru piket sudah tidak tersedia.');
            }
            if (! (bool) ($schedule->aktif ?? true)) {
                throw new HttpException(422, 'Jadwal guru piket tidak aktif.');
            }
            if ((int) $status->guru_id !== (int) $schedule->guru_id || $status->peran !== 'utama') {
                throw new HttpException(422, 'Pembatalan verifikasi hanya berlaku untuk guru piket utama.');
            }
            if (! $status->waktu_konfirmasi || $status->status === 'belum_konfirmasi') {
                throw new HttpException(422, 'Status guru piket sudah belum terverifikasi.');
            }

            $now = now('Asia/Jakarta');
            $wasAfterCutoff = $this->assignments->isPastCutoff($now);
            $oldStatus = $status->status;
            $oldConfirmationTime = $status->waktu_konfirmasi;
            $beforeStatus = $status->replicate();

            $replacements = DB::table('guru_piket_replacements')
                ->where('guru_piket_id', $status->guru_piket_id)
                ->whereDate('tanggal', $status->tanggal)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get();

            $replacementIds = $replacements->pluck('id')->map(fn ($id) => (int) $id)->all();
            $replacementTeacherIds = $replacements->pluck('guru_pengganti_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

            if ($replacementIds) {
                DB::table('guru_piket_replacements')->whereIn('id', $replacementIds)->update([
                    'status_penugasan' => 'dibatalkan',
                    'selesai_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $status->forceFill([
                'status' => 'belum_konfirmasi',
                'waktu_konfirmasi' => null,
                'dipilih_oleh' => null,
                'sumber' => 'admin_reset',
                'keterangan' => trim($reason),
            ])->save();

            $this->deactivateDailyQr((int) $status->guru_piket_id, (string) $status->tanggal, $schedule, $replacementIds, $replacementTeacherIds, $now);

            $afterStatus = $status->fresh();
            $auditPayloadBefore = [
                'status' => $beforeStatus,
                'replacements' => $replacements,
            ];
            $auditPayloadAfter = [
                'status' => $afterStatus,
                'status_lama' => $oldStatus,
                'waktu_verifikasi_lama' => $oldConfirmationTime,
                'status_baru' => 'belum_konfirmasi',
                'reset_oleh' => $adminId,
                'reset_at' => $now->toDateTimeString(),
                'setelah_cutoff' => $wasAfterCutoff,
                'replacement_ids_dinonaktifkan' => $replacementIds,
                'replacement_teacher_ids_dinonaktifkan' => $replacementTeacherIds,
            ];

            $this->audit->record(
                'cancel_duty_teacher_verification',
                'guru_piket_statuses',
                (int) $status->id,
                $auditPayloadBefore,
                $auditPayloadAfter,
                $request,
                $reason
            );

            $this->notifyTeachers($schedule, (string) $status->tanggal, $replacementTeacherIds);

            return (object) [
                'status' => $afterStatus,
                'old_status' => $oldStatus,
                'old_confirmation_time' => $oldConfirmationTime,
                'disabled_replacements' => $replacements,
                'after_cutoff' => $wasAfterCutoff,
            ];
        });
    }

    private function deactivateDailyQr(int $scheduleId, string $date, object $schedule, array $replacementIds, array $replacementTeacherIds, Carbon $now): void
    {
        if (! Schema::hasTable('qr_codes') || ! Schema::hasColumn('qr_codes', 'aktif')) {
            return;
        }

        $query = DB::table('qr_codes')->whereDate('tanggal', $date)->where('aktif', true);

        $query->where(function ($where) use ($scheduleId, $schedule, $replacementIds, $replacementTeacherIds) {
            if (Schema::hasColumn('qr_codes', 'guru_piket_id')) {
                $where->orWhere('guru_piket_id', $scheduleId);
            }
            if ($replacementIds && Schema::hasColumn('qr_codes', 'replacement_id')) {
                $where->orWhereIn('replacement_id', $replacementIds);
            }
            if ($replacementTeacherIds && Schema::hasColumn('qr_codes', 'active_teacher_id')) {
                $where->orWhereIn('active_teacher_id', $replacementTeacherIds);
            }
            if (Schema::hasColumn('qr_codes', 'guru_piket_team_key')) {
                $teamKey = implode('|', [
                    $schedule->tahun_ajaran_id ?? 'aktif',
                    strtolower((string) $schedule->hari),
                    $schedule->jam_mulai ?: '-',
                    $schedule->jam_selesai ?: '-',
                ]);
                $where->orWhere('guru_piket_team_key', $teamKey);
            }
            if (Schema::hasColumn('qr_codes', 'guru_piket_ids')) {
                $where->orWhere('guru_piket_ids', 'like', '%'.$scheduleId.'%');
            }
        });

        $payload = ['aktif' => false, 'updated_at' => $now];
        if (Schema::hasColumn('qr_codes', 'deactivated_at')) {
            $payload['deactivated_at'] = $now;
        }

        $query->update($payload);
    }

    private function notifyTeachers(object $schedule, string $date, array $replacementTeacherIds): void
    {
        if (function_exists('buatNotifikasi')) {
            buatNotifikasi([
                'user_id' => $schedule->guru_id,
                'judul' => 'Verifikasi Kehadiran Dibatalkan',
                'pesan' => 'Verifikasi kehadiran Anda untuk tanggal '.$date.' telah dibatalkan oleh admin. Silakan lakukan verifikasi ulang.',
                'kategori' => 'reset_verifikasi_piket',
                'severity' => 'warning',
                'source_type' => 'guru_pikets',
                'source_id' => $schedule->id,
                'payload' => ['tanggal' => $date],
            ]);

            foreach ($replacementTeacherIds as $teacherId) {
                buatNotifikasi([
                    'user_id' => $teacherId,
                    'judul' => 'Penugasan Pengganti Dibatalkan',
                    'pesan' => 'Penugasan Anda sebagai guru pengganti pada tanggal '.$date.' telah dibatalkan karena verifikasi guru utama direset oleh admin.',
                    'kategori' => 'reset_pengganti_piket',
                    'severity' => 'info',
                    'source_type' => 'guru_piket_replacements',
                    'source_id' => $schedule->id,
                    'payload' => ['tanggal' => $date],
                ]);
            }
        }
    }
}
