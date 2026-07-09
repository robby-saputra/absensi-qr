<?php

namespace App\Services;

use App\Models\GuruPiketStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Service ini menyelesaikan status guru piket otomatis setelah batas konfirmasi terlewati.
class FinalizeDutyTeacherStatusService
{
    // Menjalankan finalisasi untuk jadwal piket hari ini setelah cutoff.
    public function run(?string $date = null): int
    {
        $date ??= now('Asia/Jakarta')->toDateString();
        $assignmentService = app(DutyTeacherAssignmentService::class);
        if ($date !== now('Asia/Jakarta')->toDateString() || ! $assignmentService->isPastCutoff(now('Asia/Jakarta'))) {
            return 0;
        }
        $day = strtolower(Carbon::parse($date)->locale('id')->translatedFormat('l'));
        $changed = 0;

        // Semua perubahan dilakukan dalam transaksi agar status jadwal tetap konsisten.
        DB::transaction(function () use ($date, $day, $assignmentService, &$changed) {
            $schedules = DB::table('guru_pikets')->where('hari', $day)->where('aktif', 1)->whereNull('deleted_at')->lockForUpdate()->get();
            foreach ($schedules as $schedule) {
                $changed += $this->finalize((int) $schedule->id, (int) $schedule->guru_id, $date, 'utama', null, 'system_cutoff');
                $latest = DB::table('guru_piket_replacements')->where('guru_piket_id', $schedule->id)->whereDate('tanggal', $date)
                    ->whereIn('status_penugasan', ['menunggu_konfirmasi', 'aktif'])->whereNull('deleted_at')->orderByDesc('urutan_penggantian')->first();
                if ($latest) {
                    $changed += $this->finalize((int) $schedule->id, (int) $latest->guru_pengganti_id, $date, $latest->urutan_penggantian === 1 ? 'pengganti_pertama' : 'pengganti_lanjutan', (int) $schedule->guru_id, 'system_cutoff');
                    $replacementStatus = GuruPiketStatus::query()->where('guru_piket_id', $schedule->id)
                        ->where('guru_id', $latest->guru_pengganti_id)->whereDate('tanggal', $date)->first();
                    if ($replacementStatus?->status === 'hadir' && $latest->status_penugasan !== 'aktif') {
                        $assignmentService->markReplacementStatus((int) $schedule->id, (int) $latest->guru_pengganti_id, $date, 'hadir');
                    }
                }
            }
        });

        return $changed;
    }

    // Membuat atau memperbarui status hadir otomatis untuk guru yang belum konfirmasi.
    private function finalize(int $scheduleId, int $teacherId, string $date, string $role, ?int $replacedId, string $source): int
    {
        $existing = GuruPiketStatus::query()->where('guru_piket_id', $scheduleId)->where('guru_id', $teacherId)->whereDate('tanggal', $date)->lockForUpdate()->first();
        if ($existing && ($existing->status !== 'belum_konfirmasi' || $existing->sumber === 'admin_reset')) {
            return 0;
        }
        GuruPiketStatus::query()->updateOrCreate(
            ['guru_piket_id' => $scheduleId, 'guru_id' => $teacherId, 'tanggal' => $date],
            ['status' => 'hadir', 'peran' => $role, 'menggantikan_guru_id' => $replacedId, 'waktu_konfirmasi' => now('Asia/Jakarta'), 'dipilih_oleh' => null, 'sumber' => $source, 'keterangan' => 'Otomatis hadir karena tidak memilih kondisi sampai batas pukul 07.00 WIB']
        );
        app(AttendanceAuditService::class)->record('auto_teacher_present', 'guru_piket_statuses', null, $existing, ['guru_piket_id' => $scheduleId, 'guru_id' => $teacherId, 'tanggal' => $date, 'status' => 'hadir'], null, 'Batas konfirmasi guru terlewati');

        return 1;
    }
}
