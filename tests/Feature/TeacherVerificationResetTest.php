<?php

namespace Tests\Feature;

use App\Actions\ResetDutyTeacherVerificationAction;
use App\Actions\ResetSubjectTeacherVerificationAction;
use App\Services\ActiveDutyTeacherResolver;
use App\Services\ActiveTeachingTeacherResolver;
use App\Services\DutyTeacherAssignmentService;
use App\Services\DutyTeacherAttendanceService;
use Carbon\Carbon;
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

        Carbon::setTestNow(Carbon::parse('2026-07-06 06:31:00', 'Asia/Jakarta'));

        try {
            $state = app(ActiveTeachingTeacherResolver::class)->resolve($data['schedule_id'], '2026-07-06');
            $this->assertSame('belum_konfirmasi', $state->raw_status);
            $this->assertSame('menunggu_verifikasi_ulang', $state->primary_status);
            $this->assertNull($state->active_teacher_id);
            $this->assertTrue($state->requires_admin_attention);
        } finally {
            Carbon::setTestNow();
        }
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

    public function test_subject_teacher_without_manual_status_becomes_automatic_present_after_cutoff(): void
    {
        $teacherId = $this->makeUser('guru', 'Guru Mapel Otomatis');
        $classId = DB::table('kelas')->insertGetId(['nama_kelas' => 'X Auto '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $subjectId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Mapel Auto '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $scheduleId = DB::table('jadwal_pelajarans')->insertGetId([
            'kelas_id' => $classId,
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'mapel_id' => $subjectId,
            'guru_id' => $teacherId,
            'status_guru' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('jadwal_guru_statuses')->insert([
            'jadwal_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'status_guru' => 'normal',
            'status_dipilih_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 06:31:00', 'Asia/Jakarta'));

        try {
            $state = app(ActiveTeachingTeacherResolver::class)->resolve($scheduleId, '2026-07-06');

            $this->assertSame('belum_konfirmasi', $state->raw_status);
            $this->assertSame('hadir_otomatis', $state->effective_status);
            $this->assertSame('Hadir Otomatis', $state->status_label);
            $this->assertSame('otomatis_cutoff', $state->status_source);
            $this->assertFalse($state->is_manual);
            $this->assertTrue($state->is_automatic_cutoff);
            $this->assertFalse($state->requires_admin_attention);
            $this->assertSame($teacherId, $state->active_teacher_id);
            $this->assertNull($state->active_replacement);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_monitoring_shows_cancel_button_for_automatic_duty_and_subject_statuses(): void
    {
        $adminId = $this->makeUser('admin', 'Admin Monitoring Auto');
        $dutyTeacherId = $this->makeUser('guru', 'Guru Piket Auto Monitoring');
        $dutyBackupId = $this->makeUser('guru', 'Cadangan Piket Auto Monitoring');
        $subjectTeacherId = $this->makeUser('guru', 'Guru Mapel Auto Monitoring');
        $classId = DB::table('kelas')->insertGetId(['nama_kelas' => 'X Monitor '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $subjectId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Mapel Monitor '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);

        DB::table('guru_pikets')->insert([
            'id' => 99001,
            'guru_id' => $dutyTeacherId,
            'guru_pengganti_id' => $dutyBackupId,
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'Akan Bertugas',
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('jadwal_pelajarans')->insert([
            'id' => 99002,
            'kelas_id' => $classId,
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'mapel_id' => $subjectId,
            'guru_id' => $subjectTeacherId,
            'status_guru' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 08:01:00', 'Asia/Jakarta'));

        try {
            $this->withSession(['user' => (object) ['id' => $adminId, 'role' => 'admin']])
                ->get('/dashboard/admin/monitoring-verifikasi-guru?tanggal=2026-07-06&tab=piket')
                ->assertOk()
                ->assertSee('Hadir Otomatis')
                ->assertSee('Cadangan Piket Auto Monitoring')
                ->assertSee('Batalkan Verifikasi');

            $this->withSession(['user' => (object) ['id' => $adminId, 'role' => 'admin']])
                ->get('/dashboard/admin/monitoring-verifikasi-guru?tanggal=2026-07-06&tab=mapel')
                ->assertOk()
                ->assertSee('Hadir Otomatis')
                ->assertSee('Batalkan Verifikasi');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_can_reset_automatic_duty_status_without_it_returning_to_automatic(): void
    {
        $adminId = $this->makeUser('admin', 'Admin Reset Auto Piket');
        $teacherId = $this->makeUser('guru', 'Guru Piket Otomatis Reset');
        $backupId = $this->makeUser('guru', 'Cadangan Piket Otomatis Reset');
        $scheduleId = DB::table('guru_pikets')->insertGetId([
            'guru_id' => $teacherId,
            'guru_pengganti_id' => $backupId,
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'Akan Bertugas',
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $replacementId = DB::table('guru_piket_replacements')->insertGetId([
            'guru_piket_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'guru_pengganti_id' => $backupId,
            'urutan_penggantian' => 1,
            'status_penugasan' => 'aktif',
            'mulai_aktif_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 08:01:00', 'Asia/Jakarta'));

        try {
            app(ResetDutyTeacherVerificationAction::class)->executeAutomatic($scheduleId, '2026-07-06', $adminId, 'Reset otomatis piket.', null);

            $state = app(DutyTeacherAttendanceService::class)->buildDutyState(
                DB::table('guru_pikets')->where('id', $scheduleId)->first(),
                '2026-07-06'
            );

            $this->assertSame('menunggu_verifikasi_ulang', $state->primary_effective_status);
            $this->assertNull($state->active_teacher_id);
            $this->assertDatabaseHas('guru_piket_replacements', ['id' => $replacementId, 'status_penugasan' => 'dibatalkan']);
            $this->assertDatabaseHas('guru_piket_statuses', ['guru_piket_id' => $scheduleId, 'guru_id' => $teacherId, 'sumber' => 'admin_reset']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_admin_can_reset_automatic_subject_status_without_it_returning_to_automatic(): void
    {
        $adminId = $this->makeUser('admin', 'Admin Reset Auto Mapel');
        $teacherId = $this->makeUser('guru', 'Guru Mapel Otomatis Reset');
        $classId = DB::table('kelas')->insertGetId(['nama_kelas' => 'X Auto Reset '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $subjectId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Mapel Auto Reset '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $scheduleId = DB::table('jadwal_pelajarans')->insertGetId([
            'kelas_id' => $classId,
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'mapel_id' => $subjectId,
            'guru_id' => $teacherId,
            'status_guru' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 08:01:00', 'Asia/Jakarta'));

        try {
            app(ResetSubjectTeacherVerificationAction::class)->executeAutomatic($scheduleId, '2026-07-06', $adminId, 'Reset otomatis mapel.', null);

            $state = app(ActiveTeachingTeacherResolver::class)->resolve($scheduleId, '2026-07-06');

            $this->assertSame('menunggu_verifikasi_ulang', $state->effective_status);
            $this->assertNull($state->active_teacher_id);
            $this->assertDatabaseHas('jadwal_guru_statuses', [
                'jadwal_id' => $scheduleId,
                'status_guru' => 'normal',
                'status_dipilih_at' => null,
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_regular_duty_teacher_cannot_verify_after_cutoff_without_admin_reset(): void
    {
        $teacherId = $this->makeUser('guru', 'Guru Piket Tanpa Reset');
        DB::table('guru_pikets')->insert([
            'guru_id' => $teacherId,
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'Akan Bertugas',
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 07:01:00', 'Asia/Jakarta'));

        try {
            $this->withSession(['user' => (object) ['id' => $teacherId, 'role' => 'guru', 'nama' => 'Guru Piket Tanpa Reset']])
                ->post('/dashboard/piket/status', ['status' => 'hadir'])
                ->assertSessionHas('error');

            $this->assertDatabaseMissing('guru_piket_statuses', [
                'guru_id' => $teacherId,
                'sumber' => 'manual_setelah_reset_admin',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reset_duty_teacher_can_verify_after_cutoff_once(): void
    {
        $teacherId = $this->makeUser('guru', 'Guru Piket Reset Bypass');
        $scheduleId = DB::table('guru_pikets')->insertGetId([
            'guru_id' => $teacherId,
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'Akan Bertugas',
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('guru_piket_statuses')->insert([
            'guru_piket_id' => $scheduleId,
            'guru_id' => $teacherId,
            'tanggal' => '2026-07-06',
            'status' => 'belum_konfirmasi',
            'peran' => 'utama',
            'waktu_konfirmasi' => null,
            'sumber' => 'admin_reset',
            'keterangan' => 'Reset admin.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 07:01:00', 'Asia/Jakarta'));

        try {
            $this->withSession(['user' => (object) ['id' => $teacherId, 'role' => 'guru', 'nama' => 'Guru Piket Reset Bypass']])
                ->post('/dashboard/piket/status', ['status' => 'hadir'])
                ->assertSessionHas('success');

            $this->assertDatabaseHas('guru_piket_statuses', [
                'guru_piket_id' => $scheduleId,
                'guru_id' => $teacherId,
                'status' => 'hadir',
                'sumber' => 'manual_setelah_reset_admin',
            ]);
            $this->assertDatabaseHas('attendance_audit_logs', [
                'action' => 'duty_verify_after_reset',
                'table_name' => 'guru_piket_statuses',
            ]);

            $this->withSession(['user' => (object) ['id' => $teacherId, 'role' => 'guru', 'nama' => 'Guru Piket Reset Bypass']])
                ->post('/dashboard/piket/status', ['status' => 'sakit'])
                ->assertSessionHas('error');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_regular_subject_teacher_cannot_verify_after_cutoff_without_admin_reset(): void
    {
        $teacherId = $this->makeUser('guru', 'Guru Mapel Tanpa Reset');
        $classId = DB::table('kelas')->insertGetId(['nama_kelas' => 'X No Reset '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $subjectId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Mapel No Reset '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $scheduleId = DB::table('jadwal_pelajarans')->insertGetId([
            'kelas_id' => $classId,
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'mapel_id' => $subjectId,
            'guru_id' => $teacherId,
            'status_guru' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 06:31:00', 'Asia/Jakarta'));

        try {
            $this->withSession(['user' => (object) ['id' => $teacherId, 'role' => 'guru', 'nama' => 'Guru Mapel Tanpa Reset']])
                ->post('/dashboard/guru/jadwal/'.$scheduleId.'/status-guru', ['status_guru' => 'normal'])
                ->assertSessionHas('error');

            $this->assertDatabaseMissing('jadwal_guru_statuses', [
                'jadwal_id' => $scheduleId,
                'status_dipilih_at' => now('Asia/Jakarta')->toDateTimeString(),
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reset_subject_teacher_can_verify_after_cutoff_once(): void
    {
        $teacherId = $this->makeUser('guru', 'Guru Mapel Reset Bypass');
        $classId = DB::table('kelas')->insertGetId(['nama_kelas' => 'X Reset Bypass '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $subjectId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Mapel Reset Bypass '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $scheduleId = DB::table('jadwal_pelajarans')->insertGetId([
            'kelas_id' => $classId,
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'mapel_id' => $subjectId,
            'guru_id' => $teacherId,
            'status_guru' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('jadwal_guru_statuses')->insert([
            'jadwal_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'status_guru' => 'normal',
            'alasan_tidak_hadir' => 'admin_reset:Reset admin.',
            'status_dipilih_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 06:31:00', 'Asia/Jakarta'));

        try {
            $this->withSession(['user' => (object) ['id' => $teacherId, 'role' => 'guru', 'nama' => 'Guru Mapel Reset Bypass']])
                ->post('/dashboard/guru/jadwal/'.$scheduleId.'/status-guru', ['status_guru' => 'normal'])
                ->assertSessionHas('success');

            $this->assertDatabaseHas('jadwal_guru_statuses', [
                'jadwal_id' => $scheduleId,
                'status_guru' => 'normal',
                'alasan_tidak_hadir' => null,
            ]);
            $this->assertDatabaseHas('attendance_audit_logs', [
                'action' => 'subject_verify_after_reset',
                'table_name' => 'jadwal_guru_statuses',
            ]);

            $this->withSession(['user' => (object) ['id' => $teacherId, 'role' => 'guru', 'nama' => 'Guru Mapel Reset Bypass']])
                ->post('/dashboard/guru/jadwal/'.$scheduleId.'/status-guru', ['status_guru' => 'sakit'])
                ->assertSessionHas('error');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_duty_replacement_does_not_become_automatic_present_after_cutoff(): void
    {
        $teacherId = $this->makeUser('guru', 'Guru Piket Utama Replacement Wait');
        $replacementId = $this->makeUser('guru', 'Guru Piket Pengganti Wait');
        $scheduleId = DB::table('guru_pikets')->insertGetId([
            'guru_id' => $teacherId,
            'guru_pengganti_id' => $replacementId,
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'Akan Bertugas',
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('guru_piket_statuses')->insert([
            'guru_piket_id' => $scheduleId,
            'guru_id' => $teacherId,
            'tanggal' => '2026-07-06',
            'status' => 'sakit',
            'peran' => 'utama',
            'waktu_konfirmasi' => '2026-07-06 06:30:00',
            'dipilih_oleh' => $teacherId,
            'sumber' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('guru_piket_replacements')->insert([
            'guru_piket_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'guru_pengganti_id' => $replacementId,
            'urutan_penggantian' => 1,
            'status_penugasan' => 'menunggu_konfirmasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 07:01:00', 'Asia/Jakarta'));

        try {
            $state = app(DutyTeacherAttendanceService::class)->buildDutyState(
                DB::table('guru_pikets')->where('id', $scheduleId)->first(),
                '2026-07-06'
            );
            $assignment = app(ActiveDutyTeacherResolver::class)->resolve(
                (object) ['id' => $replacementId, 'role' => 'guru'],
                '2026-07-06'
            );
            $permissions = app(DutyTeacherAssignmentService::class)->permissions($scheduleId, $replacementId, '2026-07-06');

            $this->assertSame('menunggu_konfirmasi', $state->replacement_effective_status);
            $this->assertSame('menunggu_konfirmasi', $assignment->status);
            $this->assertNull($state->active_teacher_id);
            $this->assertFalse($permissions['can_manage_qr']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_duty_replacement_can_confirm_after_cutoff_when_assigned(): void
    {
        $teacherId = $this->makeUser('guru', 'Guru Piket Utama Replacement Confirm');
        $replacementId = $this->makeUser('guru', 'Guru Piket Pengganti Confirm');
        $scheduleId = DB::table('guru_pikets')->insertGetId([
            'guru_id' => $teacherId,
            'guru_pengganti_id' => $replacementId,
            'hari' => 'senin',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '12:00:00',
            'status' => 'Akan Bertugas',
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('guru_piket_statuses')->insert([
            'guru_piket_id' => $scheduleId,
            'guru_id' => $teacherId,
            'tanggal' => '2026-07-06',
            'status' => 'izin',
            'peran' => 'utama',
            'waktu_konfirmasi' => '2026-07-06 06:30:00',
            'dipilih_oleh' => $teacherId,
            'sumber' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('guru_piket_replacements')->insert([
            'guru_piket_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'guru_pengganti_id' => $replacementId,
            'urutan_penggantian' => 1,
            'status_penugasan' => 'menunggu_konfirmasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 07:01:00', 'Asia/Jakarta'));

        try {
            $this->withSession(['user' => (object) ['id' => $replacementId, 'role' => 'guru', 'nama' => 'Guru Piket Pengganti Confirm']])
                ->post('/dashboard/piket/status', ['status' => 'hadir'])
                ->assertSessionHas('success');

            $this->assertDatabaseHas('guru_piket_statuses', [
                'guru_piket_id' => $scheduleId,
                'guru_id' => $replacementId,
                'status' => 'hadir',
                'sumber' => 'manual_pengganti',
            ]);
            $this->assertDatabaseHas('guru_piket_replacements', [
                'guru_piket_id' => $scheduleId,
                'guru_pengganti_id' => $replacementId,
                'status_penugasan' => 'aktif',
            ]);
            $this->assertTrue(app(DutyTeacherAssignmentService::class)->permissions($scheduleId, $replacementId, '2026-07-06')['can_manage_qr']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_subject_replacement_waiting_confirmation_is_not_active_after_cutoff(): void
    {
        $teacherId = $this->makeUser('guru', 'Guru Mapel Utama Replacement Wait');
        $replacementId = $this->makeUser('guru', 'Guru Mapel Pengganti Wait');
        $classId = DB::table('kelas')->insertGetId(['nama_kelas' => 'X Subject Replacement '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $subjectId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Mapel Subject Replacement '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $scheduleId = DB::table('jadwal_pelajarans')->insertGetId([
            'kelas_id' => $classId,
            'hari' => 'senin',
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '09:00:00',
            'mapel_id' => $subjectId,
            'guru_id' => $teacherId,
            'guru_pengganti_id' => $replacementId,
            'status_guru' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('jadwal_guru_statuses')->insert([
            'jadwal_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'guru_pengganti_id' => $replacementId,
            'status_guru' => 'sakit',
            'alasan_tidak_hadir' => 'Sakit',
            'status_dipilih_at' => '2026-07-06 06:20:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('jadwal_guru_replacements')->insert([
            'jadwal_id' => $scheduleId,
            'tanggal' => '2026-07-06',
            'guru_utama_id' => $teacherId,
            'guru_pengganti_id' => $replacementId,
            'urutan_penggantian' => 1,
            'status_penugasan' => 'menunggu_konfirmasi',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-06 06:31:00', 'Asia/Jakarta'));

        try {
            $state = app(ActiveTeachingTeacherResolver::class)->resolve($scheduleId, '2026-07-06');

            $this->assertNull($state->active_teacher_id);
            $this->assertNull($state->active_replacement);
            $this->assertSame('menunggu_konfirmasi', $state->latest_replacement->status_penugasan);
            $this->assertFalse($state->needs_replacement);
        } finally {
            Carbon::setTestNow();
        }
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
