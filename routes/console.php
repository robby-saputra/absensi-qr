<?php

use App\Support\AbsensiRekapSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\AttendanceIntegrityService;
use App\Services\FinalizeDutyTeacherStatusService;
use App\Services\MobileAttendanceReminderService;

require_once app_path('Support/api_helpers.php');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('absensi:sinkron-rekap {--tanggal=}', function () {
    $result = AbsensiRekapSync::harian($this->option('tanggal') ?: null);
    $this->info($result['message']);
})->purpose('Sinkronkan absensi harian agar masuk ke rekap admin');

Schedule::command('absensi:sinkron-rekap')->dailyAt('23:55');
Schedule::command('attendance:finalize-teacher-status')->everyMinute()->withoutOverlapping();
Schedule::command('mobile:attendance-reminders')->everyMinute()->withoutOverlapping();

Artisan::command('mobile:attendance-reminders', function (MobileAttendanceReminderService $service) {
    $result = $service->run();
    $this->info("Pengingat mapel: {$result['mapel']}; pengingat pulang: {$result['pulang']}.");
})->purpose('Kirim pengingat FCM mapel aktif dan absen pulang ke siswa serta orang tua');

Artisan::command('attendance:finalize-teacher-status {--tanggal=}', function (FinalizeDutyTeacherStatusService $service) {
    $this->info('Status guru diselesaikan: '.$service->run($this->option('tanggal') ?: null));
})->purpose('Finalisasi status hadir guru bertugas setelah cutoff');

Artisan::command('attendance:audit', function (AttendanceIntegrityService $service) {
    foreach ($service->audit() as $key => $value) {
        $this->line($key.': '.$value);
    }
})->purpose('Audit integritas data absensi tanpa mengubah data');

Artisan::command('attendance:repair {--dry-run}', function (AttendanceIntegrityService $service) {
    $dryRun = (bool) $this->option('dry-run');
    foreach ($service->repair($dryRun) as $key => $value) {
        $this->line($key.': '.(is_bool($value) ? ($value ? 'true' : 'false') : $value));
    }
})->purpose('Periksa perbaikan aman absensi; gunakan --dry-run untuk simulasi');
