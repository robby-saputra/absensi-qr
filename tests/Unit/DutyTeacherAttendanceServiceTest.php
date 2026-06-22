<?php

namespace Tests\Unit;

use App\Services\DutyTeacherAttendanceService;
use App\Models\GuruPiketStatus;
use Carbon\Carbon;
use Tests\TestCase;

class DutyTeacherAttendanceServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_unconfirmed_schedule_is_not_considered_present(): void
    {
        Carbon::setTestNow('2026-06-22 08:00:00');
        $schedule = (object) ['id' => 1, 'jam_selesai' => '14:00:00', 'status_harian' => null];

        $this->assertSame('Belum Konfirmasi', (new DutyTeacherAttendanceService)->labelFor($schedule, '2026-06-22'));
    }

    public function test_previous_monday_status_is_not_used_for_next_monday(): void
    {
        Carbon::setTestNow('2026-06-29 08:00:00');
        $nextMonday = (object) ['id' => 1, 'jam_selesai' => '14:00:00', 'status_harian' => null];

        $this->assertSame('Belum Konfirmasi', (new DutyTeacherAttendanceService)->labelFor($nextMonday, '2026-06-29'));
    }

    public function test_daily_sick_and_leave_labels_are_date_scoped_inputs(): void
    {
        Carbon::setTestNow('2026-06-22 08:00:00');
        $service = new DutyTeacherAttendanceService;

        $this->assertSame('Sakit', $service->labelFor((object) ['id' => 1, 'jam_selesai' => '14:00:00', 'status_harian' => 'sakit'], '2026-06-22'));
        $this->assertSame('Izin', $service->labelFor((object) ['id' => 1, 'jam_selesai' => '14:00:00', 'status_harian' => 'izin'], '2026-06-22'));
    }

    public function test_unconfirmed_daily_record_keeps_form_open(): void
    {
        $status = new GuruPiketStatus(['status' => 'belum_konfirmasi']);
        $this->assertFalse((new DutyTeacherAttendanceService)->hasConfirmed($status));
    }

    public function test_final_daily_status_locks_only_its_own_schedule(): void
    {
        $service = new DutyTeacherAttendanceService;
        $guruA = new GuruPiketStatus(['status' => 'hadir', 'waktu_konfirmasi' => Carbon::parse('2026-06-29 07:00:00')]);
        $guruB = null;

        $this->assertTrue($service->hasConfirmed($guruA));
        $this->assertFalse($service->hasConfirmed($guruB));
    }

    public function test_archived_or_previous_status_is_represented_as_no_daily_status(): void
    {
        $service = new DutyTeacherAttendanceService;
        $this->assertSame('belum_konfirmasi', $service->currentStatus(null));
        $this->assertFalse($service->hasConfirmed(null));
    }

    public function test_replacement_is_active_only_when_primary_is_sick_or_on_leave_today(): void
    {
        $service = new DutyTeacherAttendanceService;
        $present = new GuruPiketStatus(['status' => 'hadir']);
        $sick = new GuruPiketStatus(['status' => 'sakit']);
        $leave = new GuruPiketStatus(['status' => 'izin']);

        $this->assertFalse($service->isReplacementActive(null));
        $this->assertFalse($service->isReplacementActive($present));
        $this->assertTrue($service->isReplacementActive($sick));
        $this->assertTrue($service->isReplacementActive($leave));
    }

    public function test_primary_status_does_not_confirm_replacement_status(): void
    {
        $service = new DutyTeacherAttendanceService;
        $primary = new GuruPiketStatus(['status' => 'sakit', 'waktu_konfirmasi' => Carbon::parse('2026-06-29 07:00:00')]);
        $replacement = null;

        $this->assertTrue($service->isReplacementActive($primary));
        $this->assertFalse($service->hasConfirmed($replacement));
    }

    public function test_daily_status_key_separates_schedule_and_teacher(): void
    {
        $service = new DutyTeacherAttendanceService;

        $this->assertSame('10:25', $service->statusKey(10, 25));
        $this->assertNotSame($service->statusKey(10, 25), $service->statusKey(10, 26));
        $this->assertNotSame($service->statusKey(10, 25), $service->statusKey(11, 25));
    }

    public function test_status_action_contains_no_qr_payload_block(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Dashboard/PiketDashboardController.php'));
        $statusMethod = strstr($source, 'public function status(');
        $statusMethod = strstr($statusMethod, 'private function authorizeDutyPermission', true);

        $this->assertStringNotContainsString("\$payload['guru_piket_id']", $statusMethod);
        $this->assertStringNotContainsString("\$teamBase->id", $statusMethod);
        $this->assertStringNotContainsString("\$activeAssignment?->assignment", $statusMethod);
    }
}
