<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class TeachingPeriodService
{
    public const TOLERANCE_KEY = 'toleransi_telat_mapel_menit';

    public function toleranceMinutes(): int
    {
        return max(0, (int) (DB::table('attendance_settings')->where('key', self::TOLERANCE_KEY)->value('value') ?? 10));
    }

    public function attendanceStatus(CarbonInterface $scanAt, string $startTime): string
    {
        $deadline = $scanAt->copy()->startOfDay()->setTimeFromTimeString($startTime)->addMinutes($this->toleranceMinutes());

        return $scanAt->lte($deadline) ? 'hadir' : 'terlambat';
    }

    public function endPeriod(int $startPeriod, int $periodCount): int
    {
        return $startPeriod + max(1, $periodCount) - 1;
    }

    public function assertValidRange(int $startPeriod, int $periodCount): void
    {
        abort_if($startPeriod < 1 || $periodCount < 1 || $this->endPeriod($startPeriod, $periodCount) > 14, 422, 'Rentang JP tidak valid.');
    }
}
