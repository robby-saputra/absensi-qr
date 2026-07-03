<?php

namespace App\Services;

use App\Models\GuruPiketStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Service ini mengatur hak akses dan rantai pengganti untuk guru piket.
class DutyTeacherAssignmentService
{
    // Mengambil batas waktu konfirmasi guru piket dari setting, default pukul 07:00.
    public function cutoff(): string
    {
        return (string) (DB::table('attendance_settings')->where('key', 'teacher_attendance_cutoff')->value('value') ?: '07:00:00');
    }

    // Mengubah jam cutoff menjadi object waktu pada tanggal yang sedang dicek.
    public function cutoffAt(?\Carbon\CarbonInterface $at = null): \Carbon\CarbonInterface
    {
        $at ??= now('Asia/Jakarta');
        return $at->copy()->timezone('Asia/Jakarta')->startOfDay()->setTimeFromTimeString($this->cutoff());
    }

    // Mengecek apakah waktu sekarang sudah melewati batas konfirmasi.
    public function isPastCutoff(?\Carbon\CarbonInterface $at = null): bool
    {
        $at ??= now('Asia/Jakarta');
        $at = $at->copy()->timezone('Asia/Jakarta');
        return $at->gt($this->cutoffAt($at));
    }

    // Menentukan izin lihat, kelola absensi, dan kelola QR untuk guru piket tertentu.
    public function permissions(int $scheduleId, int $teacherId, string $date): array
    {
        $schedule = DB::table('guru_pikets')->where('id', $scheduleId)->whereNull('deleted_at')->first();
        if (! $schedule) return $this->none();
        $status = GuruPiketStatus::query()->where('guru_piket_id', $scheduleId)->where('guru_id', $teacherId)->whereDate('tanggal', $date)->first();
        $attendanceStatus = $this->effectiveAttendanceStatus($status?->status, $date);
        if ((int) $schedule->guru_id === $teacherId) {
            return $this->permissionDecision(true, $attendanceStatus, true, null);
        }
        $latest = DB::table('guru_piket_replacements')->where('guru_piket_id', $scheduleId)->whereDate('tanggal', $date)
            ->whereNull('deleted_at')->orderByDesc('urutan_penggantian')->first();
        $active = $latest && (int) $latest->guru_pengganti_id === $teacherId;
        return $this->permissionDecision(false, $attendanceStatus, (bool) $active, $latest?->status_penugasan);
    }

    // Menghasilkan keputusan akses berdasarkan status guru utama atau guru pengganti.
    public function permissionDecision(bool $primary, ?string $attendanceStatus, bool $latest, ?string $assignmentStatus): array
    {
        if ($primary) {
            $present = in_array($attendanceStatus, ['hadir', 'hadir_otomatis'], true);
            $absent = in_array($attendanceStatus, ['izin', 'sakit'], true);
            return ['can_view_attendance' => $present || $absent, 'can_manage_attendance' => $present, 'can_manage_qr' => $present];
        }
        $present = $latest && in_array($assignmentStatus, ['menunggu_konfirmasi', 'aktif'], true) && in_array($attendanceStatus, ['hadir', 'hadir_otomatis'], true);
        return ['can_view_attendance' => $present, 'can_manage_attendance' => $present, 'can_manage_qr' => $present];
    }

    private function effectiveAttendanceStatus(?string $status, string $date): ?string
    {
        if ($status && $status !== 'belum_konfirmasi') {
            return $status;
        }

        return $date === now('Asia/Jakarta')->toDateString() && $this->isPastCutoff(now('Asia/Jakarta'))
            ? 'hadir_otomatis'
            : ($status ?: null);
    }

    // Mengaktifkan guru pengganti pertama saat guru utama izin atau sakit.
    public function activateFirstReplacement(object $schedule, string $date, int $actorId, string $reason): ?object
    {
        if (! $schedule->guru_pengganti_id) return null;
        return DB::transaction(function () use ($schedule, $date, $actorId, $reason) {
            DB::table('guru_piket_replacements')->where('guru_piket_id', $schedule->id)->whereDate('tanggal', $date)
                ->whereNull('deleted_at')->lockForUpdate()->get();
            DB::table('guru_piket_replacements')->updateOrInsert(
                ['guru_piket_id' => $schedule->id, 'guru_pengganti_id' => $schedule->guru_pengganti_id, 'tanggal' => $date, 'deleted_at' => null],
                ['guru_utama_id' => $schedule->guru_id, 'urutan_penggantian' => 1, 'status_penugasan' => 'menunggu_konfirmasi', 'alasan' => $reason, 'ditunjuk_oleh' => $actorId, 'updated_at' => now(), 'created_at' => now()]
            );
            return DB::table('guru_piket_replacements')->where('guru_piket_id', $schedule->id)->where('guru_pengganti_id', $schedule->guru_pengganti_id)->whereDate('tanggal', $date)->whereNull('deleted_at')->first();
        });
    }

    // Menandai status guru pengganti dan menutup QR lama jika guru aktif berubah.
    public function markReplacementStatus(int $scheduleId, int $teacherId, string $date, string $status): void
    {
        DB::transaction(function () use ($scheduleId, $teacherId, $date, $status) {
            $assignment = DB::table('guru_piket_replacements')->where('guru_piket_id', $scheduleId)->where('guru_pengganti_id', $teacherId)->whereDate('tanggal', $date)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $assignment) return;
            if ($status === 'hadir') {
                DB::table('guru_piket_replacements')->where('guru_piket_id', $scheduleId)->whereDate('tanggal', $date)
                    ->where('id', '!=', $assignment->id)->where('status_penugasan', 'aktif')->whereNull('deleted_at')
                    ->update(['status_penugasan' => 'digantikan', 'selesai_at' => now(), 'updated_at' => now()]);
            }
            DB::table('guru_piket_replacements')->where('id', $assignment->id)->update([
                'status_penugasan' => $status === 'hadir' ? 'aktif' : 'berhalangan',
                'mulai_aktif_at' => $status === 'hadir' ? now() : $assignment->mulai_aktif_at,
                'selesai_at' => in_array($status, ['izin', 'sakit'], true) ? now() : null,
                'updated_at' => now(),
            ]);
            if ($status === 'hadir' && Schema::hasColumn('qr_codes', 'guru_piket_id')) {
                DB::table('qr_codes')->where('guru_piket_id', $scheduleId)->whereDate('tanggal', $date)
                    ->where('aktif', true)->where(fn ($query) => $query->whereNull('active_teacher_id')->orWhere('active_teacher_id', '!=', $teacherId))
                    ->update(['aktif' => false, 'deactivated_at' => now(), 'updated_at' => now()]);
            }
        });
    }

    // Mengambil kandidat pengganti yang aktif dan tidak bentrok jadwal.
    public function availableCandidates(object $schedule, string $date): \Illuminate\Support\Collection
    {
        $used = DB::table('guru_piket_replacements')->where('guru_piket_id', $schedule->id)->whereDate('tanggal', $date)
            ->whereNull('deleted_at')->pluck('guru_pengganti_id')->push($schedule->guru_id)->filter()->unique();
        return DB::table('users as u')->where('u.role', 'guru')->where('u.aktif', 1)->whereNull('u.deleted_at')->whereNotIn('u.id', $used)
            ->whereNotExists(function ($q) use ($date) {
                $q->selectRaw('1')->from('guru_piket_statuses as s')->whereColumn('s.guru_id', 'u.id')->whereDate('s.tanggal', $date)
                    ->whereIn('s.status', ['izin', 'sakit'])->whereNull('s.deleted_at');
            })
            ->whereNotExists(function ($q) use ($schedule) {
                $q->selectRaw('1')->from('jadwal_pelajarans as j')->where('j.hari', $schedule->hari)->whereNull('j.deleted_at')
                    ->where(fn ($w) => $w->whereColumn('j.guru_id', 'u.id')->orWhereColumn('j.guru_pengganti_id', 'u.id'))
                    ->where('j.jam_mulai', '<', $schedule->jam_selesai)->where('j.jam_selesai', '>', $schedule->jam_mulai);
            })
            ->whereNotExists(function ($q) use ($schedule) {
                $q->selectRaw('1')->from('guru_pikets as p')->whereColumn('p.guru_id', 'u.id')->where('p.hari', $schedule->hari)
                    ->where('p.id', '!=', $schedule->id)->where('p.aktif', 1)->whereNull('p.deleted_at')
                    ->where('p.jam_mulai', '<', $schedule->jam_selesai)->where('p.jam_selesai', '>', $schedule->jam_mulai);
            })->orderBy('u.nama')->select('u.id', 'u.nama')->get();
    }

    // Menunjuk pengganti lanjutan ketika pengganti sebelumnya berhalangan.
    public function assignContinuation(object $schedule, string $date, int $teacherId, int $adminId, string $reason, ?string $note = null): object
    {
        abort_unless($this->availableCandidates($schedule, $date)->contains('id', $teacherId), 422, 'Guru tidak tersedia atau memiliki jadwal bentrok.');
        return DB::transaction(function () use ($schedule, $date, $teacherId, $adminId, $reason, $note) {
            $chain = DB::table('guru_piket_replacements')->where('guru_piket_id', $schedule->id)->whereDate('tanggal', $date)
                ->whereNull('deleted_at')->lockForUpdate()->orderBy('urutan_penggantian')->get();
            $previous = $chain->last();
            abort_unless($previous && $previous->status_penugasan === 'berhalangan', 422, 'Penugasan belum membutuhkan pengganti lanjutan.');
            $order = (int) $previous->urutan_penggantian + 1;
            $id = DB::table('guru_piket_replacements')->insertGetId([
                'guru_piket_id' => $schedule->id, 'tanggal' => $date, 'guru_utama_id' => $schedule->guru_id,
                'guru_pengganti_id' => $teacherId, 'menggantikan_replacement_id' => $previous->id,
                'urutan_penggantian' => $order, 'status_penugasan' => 'menunggu_konfirmasi',
                'alasan' => trim($reason.($note ? ' - '.$note : '')), 'ditunjuk_oleh' => $adminId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('guru_piket_replacements')->where('id', $previous->id)->update(['status_penugasan' => 'digantikan', 'selesai_at' => now(), 'updated_at' => now()]);
            if ($this->isPastCutoff(now('Asia/Jakarta'))) {
                GuruPiketStatus::query()->updateOrCreate(
                    ['guru_piket_id' => $schedule->id, 'guru_id' => $teacherId, 'tanggal' => $date],
                    ['status' => 'hadir', 'peran' => 'pengganti_lanjutan', 'menggantikan_guru_id' => $schedule->guru_id, 'waktu_konfirmasi' => now(), 'dipilih_oleh' => $adminId, 'sumber' => 'system_cutoff_after_assignment', 'keterangan' => 'Hadir otomatis karena ditunjuk setelah batas konfirmasi.']
                );
                DB::table('guru_piket_replacements')->where('id', $id)->update(['status_penugasan' => 'aktif', 'mulai_aktif_at' => now(), 'updated_at' => now()]);
            }
            app(AttendanceAuditService::class)->record('assign_duty_replacement', 'guru_piket_replacements', $id, $previous, DB::table('guru_piket_replacements')->where('id', $id)->first(), request(), $reason);
            DB::table('notifications')->where('kategori', 'pengganti_piket_berhalangan')->where('source_id', $schedule->id)->where('status', 'belum_dibaca')->update(['status' => 'dibaca', 'updated_at' => now()]);
            return DB::table('guru_piket_replacements')->where('id', $id)->first();
        });
    }

    // Akses kosong dipakai saat jadwal tidak ditemukan atau guru tidak punya hak.
    private function none(): array
    {
        return ['can_view_attendance' => false, 'can_manage_attendance' => false, 'can_manage_qr' => false];
    }
}
