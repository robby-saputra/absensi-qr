<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class TeachingPeriodService
{
    // Key pengaturan ini menyimpan toleransi telat mapel dalam satuan menit.
    public const TOLERANCE_KEY = 'toleransi_telat_mapel_menit';

    public function toleranceMinutes(): int
    {
        // Jika admin belum mengatur toleransi, sistem memakai default 10 menit.
        // max(0, ...) memastikan toleransi tidak pernah bernilai negatif.
        return max(0, (int) (DB::table('attendance_settings')->where('key', self::TOLERANCE_KEY)->value('value') ?? 10));
    }

    public function attendanceStatus(CarbonInterface $scanAt, string $startTime): string
    {
        // Deadline hadir dihitung dari jam mulai pelajaran ditambah toleransi telat.
        $deadline = $scanAt->copy()->startOfDay()->setTimeFromTimeString($startTime)->addMinutes($this->toleranceMinutes());

        // Jika siswa scan sebelum atau sama dengan deadline, statusnya hadir.
        // Jika melewati deadline, statusnya terlambat.
        return $scanAt->lte($deadline) ? 'hadir' : 'terlambat';
    }

    public function endPeriod(int $startPeriod, int $periodCount): int
    {
        // Menghitung JP akhir berdasarkan JP mulai dan jumlah JP.
        // Contoh: mulai JP 3 dan jumlah 2 berarti berakhir di JP 4.
        return $startPeriod + max(1, $periodCount) - 1;
    }

    public function assertValidRange(int $startPeriod, int $periodCount): void
    {
        // Rentang JP harus masuk akal: mulai minimal 1, jumlah minimal 1, dan maksimal sampai JP 14.
        abort_if($startPeriod < 1 || $periodCount < 1 || $this->endPeriod($startPeriod, $periodCount) > 14, 422, 'Rentang JP tidak valid.');
    }
}
