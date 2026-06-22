<?php

namespace Tests\Unit;

use App\Services\ActiveTeachingTeacherResolver;
use PHPUnit\Framework\TestCase;

class ActiveTeachingTeacherResolverTest extends TestCase
{
    public function test_present_primary_is_active_and_needs_no_replacement(): void
    {
        $result = (new ActiveTeachingTeacherResolver)->decision('normal', 10, collect());
        $this->assertSame(10, $result->active_teacher_id);
        $this->assertFalse($result->needs_replacement);
    }

    public function test_absent_primary_without_assignment_needs_replacement(): void
    {
        foreach (['izin', 'sakit', 'inval'] as $status) {
            $result = (new ActiveTeachingTeacherResolver)->decision($status, 10, collect());
            $this->assertNull($result->active_teacher_id);
            $this->assertTrue($result->needs_replacement);
        }
    }

    public function test_only_active_replacement_becomes_active_teacher(): void
    {
        $chain = collect([
            (object) ['guru_pengganti_id' => 20, 'status_penugasan' => 'berhalangan'],
            (object) ['guru_pengganti_id' => 30, 'status_penugasan' => 'aktif'],
        ]);
        $result = (new ActiveTeachingTeacherResolver)->decision('izin', 10, $chain);
        $this->assertSame(30, $result->active_teacher_id);
        $this->assertFalse($result->needs_replacement);
    }

    public function test_waiting_replacement_does_not_allow_another_assignment_yet(): void
    {
        $chain = collect([(object) ['guru_pengganti_id' => 20, 'status_penugasan' => 'menunggu_konfirmasi']]);
        $result = (new ActiveTeachingTeacherResolver)->decision('sakit', 10, $chain);
        $this->assertNull($result->active_teacher_id);
        $this->assertFalse($result->needs_replacement);
    }

    public function test_failed_latest_replacement_requires_continuation(): void
    {
        $chain = collect([(object) ['guru_pengganti_id' => 20, 'status_penugasan' => 'berhalangan']]);
        $result = (new ActiveTeachingTeacherResolver)->decision('sakit', 10, $chain);
        $this->assertNull($result->active_teacher_id);
        $this->assertTrue($result->needs_replacement);
    }
}
