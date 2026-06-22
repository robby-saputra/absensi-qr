<?php

namespace Tests\Unit;

use App\Services\DutyTeacherAssignmentService;
use PHPUnit\Framework\TestCase;

class DutyTeacherAssignmentServiceTest extends TestCase
{
    public function test_present_primary_has_operational_access(): void
    {
        $this->assertSame(['can_view_attendance'=>true,'can_manage_attendance'=>true,'can_manage_qr'=>true], (new DutyTeacherAssignmentService)->permissionDecision(true, 'hadir', true, null));
    }

    public function test_absent_primary_is_read_only(): void
    {
        $service = new DutyTeacherAssignmentService;
        foreach (['izin', 'sakit'] as $status) {
            $this->assertSame(['can_view_attendance'=>true,'can_manage_attendance'=>false,'can_manage_qr'=>false], $service->permissionDecision(true, $status, true, null));
        }
    }

    public function test_latest_present_replacement_has_operational_access(): void
    {
        $this->assertSame(['can_view_attendance'=>true,'can_manage_attendance'=>true,'can_manage_qr'=>true], (new DutyTeacherAssignmentService)->permissionDecision(false, 'hadir', true, 'aktif'));
    }

    public function test_absent_or_replaced_replacement_has_no_access(): void
    {
        $service = new DutyTeacherAssignmentService;
        foreach ([['sakit',true,'berhalangan'], ['izin',true,'berhalangan'], ['hadir',false,'digantikan'], ['hadir',true,'digantikan']] as [$status,$latest,$assignment]) {
            $this->assertSame(['can_view_attendance'=>false,'can_manage_attendance'=>false,'can_manage_qr'=>false], $service->permissionDecision(false, $status, $latest, $assignment));
        }
    }
}
