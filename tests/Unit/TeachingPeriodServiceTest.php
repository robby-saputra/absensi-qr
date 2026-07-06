<?php

namespace Tests\Unit;

use App\Services\TeachingPeriodService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TeachingPeriodServiceTest extends TestCase
{
    private function service(): TeachingPeriodService
    {
        return new class extends TeachingPeriodService {
            public function toleranceMinutes(): int
            {
                return 10;
            }
        };
    }

    public function test_scan_at_tolerance_boundary_is_present(): void
    {
        $this->assertSame('hadir', $this->service()->attendanceStatus(Carbon::parse('2026-06-22 07:10:00'), '07:00:00'));
    }

    public function test_scan_after_tolerance_boundary_is_late(): void
    {
        $this->assertSame('terlambat', $this->service()->attendanceStatus(Carbon::parse('2026-06-22 07:10:01'), '07:00:00'));
    }

    public function test_end_period_is_derived_from_start_and_count(): void
    {
        $this->assertSame(6, $this->service()->endPeriod(3, 4));
    }
}
