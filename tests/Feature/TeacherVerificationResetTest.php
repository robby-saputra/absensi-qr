<?php

namespace Tests\Feature;

use App\Actions\ResetDutyTeacherVerificationAction;
use App\Actions\ResetSubjectTeacherVerificationAction;
use App\Services\ActiveTeachingTeacherResolver;
use App\Services\DutyTeacherAttendanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TeacherVerificationResetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_admin_can_reset_duty_teacher_leave_and_disable_replacement_and_qr(): void
    {
        $data = $this->makeDutyScenario('izin');

        app(ResetDutyTeacherVerificationAction::class)->execute($data['status_id'], $data['admin_id'], 'Guru utama akhirnya hadir.', null);

        $this->assertDatabaseHas('guru_piket_statuses', [
            'id' => $data['status_id'],
            'status' => 'belum_konfirmasi',
            'waktu_konfirmasi' => null,
        ]);
        $this->assertDatabaseHas('guru_piket_replacements', [
            'id' => $data['replacement_id'],
            'status_penugasan' => 'dibatalkan',
        ]);
        $this->assertDatabaseHas('qr_codes', [
            'id' => $data['qr_id'],
            'aktif' => 0,
        ]);
        $this->assertDatabaseHas('guru_pikets', [
            'id' => $data['schedule_id'],
            'guru_id' => $data['teacher_id'],
            'guru_pengganti_id' => $data['replacement_teacher_id'],
            'hari' => 'senin',
        ]);
    }

    public function test_admin_can_reset_duty_teacher_sick_with_continuation(): void
    {
        $data = $this->makeDutyScenario('sakit', true);

        app(ResetDutyTeacherVerificationAction::class)->execute($data['status_id'], $data['admin_id'], 'Reset sakit.', null);

        $this->assertDatabaseHas('guru_piket_replacements', ['id' => $data['replacement_id'], 'status_penugasan' => 'dibatalkan']);
        $this->assertDatabaseHas('guru_piket_replacements', ['id' => $data['continuation_id'], 'status_penugasan' => 'dibatalkan']);
    }

    public function test_admin_can_reset_subject_teacher_leave_and_disable_replacement_and_qr(): void
    {
        $data = $this->makeSubjectScenario('izin');

        app(ResetSubjectTeacherVerificationAction::class)->execute($data['status_id'], $data['admin_id'], 'Guru mapel hadir kembali.', null);

        $this->assertDatabaseHas('jadwal_guru_statuses', [
            'id' => $data['status_id'],
            'status_guru' => 'normal',
            'status_dipilih_at' => null,
            'guru_pengganti_id' => null,
        ]);
        $this->assertDatabaseHas('jadwal_guru_replacements', [
            'id' => $data['replacement_id'],
            'status_penugasan' => 'dibatalkan',
        ]);
        $this->assertDatabaseHas('qr_sesis', [
            'id' => $data['qr_id'],
            'aktif' => 0,
        ]);

        $state = app(ActiveTeachingTeacherResolver::class)->resolve($data['schedule_id'], '2026-07-06');
        $this->assertSame('belum_konfirmasi', $state->primary_status);
        $this->assertNull($state->active_teacher_id);
    }

    public function test_admin_can_reset_subject_teacher_sick_with_continuation(): void
    {
        $data = $this->makeSubjectScenario('sakit', true);

        app(ResetSubjectTeacherVerificationAction::class)->execute($data['status_id'], $data['admin_id'], 'Reset sakit mapel.', null);

        $this->assertDatabaseHas('jadwal_guru_replacements', ['id' => $data['replacement_id'], 'status_penugasan' => 'dibatalkan']);
        $this->assertDatabaseHas('jadwal_guru_replacements', ['id' => $data['continuation_id'], 'status_penugasan' => 'dibatalkan']);
    }

    public function test_reason_is_required_and_non_admin_is_rejected(): void
    {
        $data = $this->makeDutyScenario('izin');

        $this->withSession(['user' => (object) ['id' => $data['admin_id'], 'role' => 'admin']])
            ->post(route('admin.teacher-verifications.duty.cancel', $data['status_id']), ['alasan' => ''])
            ->assertSessionHasErrors('alasan');

        $this->withSession(['user' => (object) ['id' => $data['teacher_id'], 'role' => 'guru']])
            ->post(route('admin.teacher-verifications.duty.cancel', $data['status_id']), ['alasan' => 'Tidak boleh'])
            ->assertForbidden();
    }

    public function test_second_reset_is_rejected(): void
    {
        $data = $this->makeDutyScenario('izin');
        $action = app(ResetDutyTeacherVerificationAction::class);

        $action->execute($data['status_id'], $data['admin_id'], 'Reset pertama.', null);

        $this->expectException(HttpException::class);
        $action->execute($data['status_id'], $data['admin_id'], 'Reset kedua.', null);
    }

    public function test_build_duty_state_accepts_replacement_name_alias_from_monitoring_controller(): void
    {
        $service = app(DutyTeacherAttendanceService::class);
        $schedule = (object) [
            'id' => 9001,
            'guru_id' => 11,
            'guru_pengganti_id' => 22,
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:00:00',
            'nama_guru_utama' => 'Guru Utama',
        ];
        $statusRows = collect([
            $service->statusKey(9001, 11) => (object) [
                'status' => DutyTeacherAttendanceService::IZIN,
                'sumber' => 'manual',
            ],
        ]);
        $replacementChain = collect([
            (object) [
                'guru_piket_id' => 9001,
                'guru_pengganti_id' => 22,
                'urutan_penggantian' => 1,
                'status_penugasan' => 'aktif',
                'nama_pengganti' => 'Guru Pengganti Monitoring',
            ],
        ]);

        $state = $service->buildDutyState($schedule, '2026-07-06', $statusRows, $replacementChain);

        $this->assertSame(22, $state->active_teacher_id);
        $this->assertSame('Guru Pengganti Monitoring', $state->active_teacher_name);
        $this->assertSame('Guru Pengganti Monitoring', $state->active_label);
    }

    private function makeUser(string $role, string $name): int
    {
        return DB::table('users')->insertGetId([
            'nama' => $name,
            'username' => strtolower(str_replace(' ', '_', $name)).'_'.uniqid(),
            'password' => bcrypt('password'),
            'role' => $role,
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeDutyScenario(string $status, bool $withContinuation = false): array
    {
        $adminId = $this->makeUser('admin', 'Admin Reset');
        $teacherId = $this->makeUser('guru', 'Guru Piket Utama');
        $replacementTeacherId = $this->makeUser('guru', 'Guru Piket Pengganti');
        $continuationTeacherId = $this->makeUser('guru', 'Guru Piket Lanjutan');
        $scheduleId = DB::table('guru_pikets')->insertGetId([
            'guru_id' => $teacherId,
            'guru_pengganti_id' => $replacementTeacherId,
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'Akan Bertugas',
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $statusId = DB::table('guru_piket_statuses')->insertGetId([
            'guru_piket_id' => $scheduleId,
            'guru_id' => $teacherId,
            'tanggal' => '2026-07-06',
            'status' => $status,
            'peran' => 'utama',
            'waktu_konfirmasi' => '2026-07-06 05:30:00',
            'dipilih_oleh' => $teacherId,
            'sumber' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $replacementId = DB::table('guru_piket_replacements')->insertGetId([
            'guru_piket_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'guru_pengganti_id' => $replacementTeacherId,
            'urutan_penggantian' => 1,
            'status_penugasan' => 'aktif',
            'mulai_aktif_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $continuationId = null;
        if ($withContinuation) {
            $continuationId = DB::table('guru_piket_replacements')->insertGetId([
                'guru_piket_id' => $scheduleId,
                'tanggal' => '2026-07-06',
                'guru_utama_id' => $teacherId,
                'guru_pengganti_id' => $continuationTeacherId,
                'menggantikan_replacement_id' => $replacementId,
                'urutan_penggantian' => 2,
                'status_penugasan' => 'aktif',
                'mulai_aktif_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $qrId = DB::table('qr_codes')->insertGetId([
            'guru_piket_id' => $scheduleId,
            'active_teacher_id' => $replacementTeacherId,
            'replacement_id' => $replacementId,
            'tanggal' => '2026-07-06',
            'tipe' => 'masuk',
            'token' => 'duty-reset-'.uniqid(),
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return compact('adminId', 'teacherId', 'replacementTeacherId', 'scheduleId', 'statusId', 'replacementId', 'continuationId', 'qrId') + [
            'admin_id' => $adminId,
            'teacher_id' => $teacherId,
            'replacement_teacher_id' => $replacementTeacherId,
            'schedule_id' => $scheduleId,
            'status_id' => $statusId,
            'replacement_id' => $replacementId,
            'continuation_id' => $continuationId,
            'qr_id' => $qrId,
        ];
    }

    private function makeSubjectScenario(string $status, bool $withContinuation = false): array
    {
        $adminId = $this->makeUser('admin', 'Admin Mapel');
        $teacherId = $this->makeUser('guru', 'Guru Mapel Utama');
        $replacementTeacherId = $this->makeUser('guru', 'Guru Mapel Pengganti');
        $continuationTeacherId = $this->makeUser('guru', 'Guru Mapel Lanjutan');
        $classId = DB::table('kelas')->insertGetId(['nama_kelas' => 'X Reset '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $subjectId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Mapel Reset '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $scheduleId = DB::table('jadwal_pelajarans')->insertGetId([
            'kelas_id' => $classId,
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'mapel_id' => $subjectId,
            'guru_id' => $teacherId,
            'guru_pengganti_id' => $replacementTeacherId,
            'status_guru' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $statusId = DB::table('jadwal_guru_statuses')->insertGetId([
            'jadwal_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'guru_pengganti_id' => $replacementTeacherId,
            'status_guru' => $status,
            'alasan_tidak_hadir' => ucfirst($status),
            'status_dipilih_at' => '2026-07-06 05:45:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $replacementId = DB::table('jadwal_guru_replacements')->insertGetId([
            'jadwal_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'guru_pengganti_id' => $replacementTeacherId,
            'urutan_penggantian' => 1,
            'status_penugasan' => 'aktif',
            'status_kehadiran' => 'hadir',
            'mulai_aktif_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $continuationId = null;
        if ($withContinuation) {
            $continuationId = DB::table('jadwal_guru_replacements')->insertGetId([
                'jadwal_id' => $scheduleId,
                'tanggal' => '2026-07-06',
                'guru_utama_id' => $teacherId,
                'guru_pengganti_id' => $continuationTeacherId,
                'menggantikan_replacement_id' => $replacementId,
                'urutan_penggantian' => 2,
                'status_penugasan' => 'aktif',
                'status_kehadiran' => 'hadir',
                'mulai_aktif_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $qrId = DB::table('qr_sesis')->insertGetId([
            'jadwal_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'token' => 'subject-reset-'.uniqid(),
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'admin_id' => $adminId,
            'teacher_id' => $teacherId,
            'replacement_teacher_id' => $replacementTeacherId,
            'schedule_id' => $scheduleId,
            'status_id' => $statusId,
            'replacement_id' => $replacementId,
            'continuation_id' => $continuationId,
            'qr_id' => $qrId,
        ];
    }
}
