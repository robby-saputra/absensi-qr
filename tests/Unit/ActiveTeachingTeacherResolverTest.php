<?php

namespace Tests\Unit;

use App\Services\ActiveTeachingTeacherResolver;
use PHPUnit\Framework\TestCase;

class ActiveTeachingTeacherResolverTest extends TestCase
{
    public function test_present_primary_is_active_and_needs_no_replacement(): void
    {
        $chain = collect([(object) ['guru_pengganti_id' => 20, 'status_penugasan' => 'aktif']]);
        $result = (new ActiveTeachingTeacherResolver)->decision('normal', 10, $chain);
        $this->assertSame(10, $result->active_teacher_id);
        $this->assertNull($result->active_replacement);
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
        $this->assertSame(30, (int) $result->active_replacement->guru_pengganti_id);
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

    public function test_status_and_role_labels_match_mobile_contract(): void
    {
        $resolver = new ActiveTeachingTeacherResolver;
        $this->assertSame('Hadir', $resolver->statusLabel('normal'));
        $this->assertSame('Sakit', $resolver->statusLabel('sakit'));
        $this->assertSame('Aktif', $resolver->statusLabel('aktif'));
        $this->assertSame('Guru Utama', $resolver->roleLabel('guru_utama'));
        $this->assertSame('Guru Pengganti', $resolver->roleLabel('pengganti_pertama'));
        $this->assertSame('Guru Pengganti Lanjutan', $resolver->roleLabel('pengganti_lanjutan'));
    }
}
