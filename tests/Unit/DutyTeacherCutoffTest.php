<?php

namespace Tests\Unit;

use App\Services\DutyTeacherAssignmentService;
use Carbon\Carbon;
use Tests\TestCase;

class DutyTeacherCutoffTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_065959_is_not_past_cutoff(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-06 06:59:59', 'Asia/Jakarta'));
        $this->assertFalse(app(DutyTeacherAssignmentService::class)->isPastCutoff(now('Asia/Jakarta')));
    }

    public function test_exactly_070000_is_not_past_cutoff(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-06 07:00:00', 'Asia/Jakarta'));
        $this->assertFalse(app(DutyTeacherAssignmentService::class)->isPastCutoff(now('Asia/Jakarta')));
    }

    public function test_070001_is_past_cutoff(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-06 07:00:01', 'Asia/Jakarta'));
        $this->assertTrue(app(DutyTeacherAssignmentService::class)->isPastCutoff(now('Asia/Jakarta')));
    }
}
