<?php

use App\Services\AttendanceIntegrityService;
use App\Services\FinalizeDutyTeacherStatusService;
use App\Services\MobileAttendanceReminderService;
use App\Support\AbsensiRekapSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

require_once app_path('Support/api_helpers.php');

// File ini mendefinisikan command artisan dan jadwal otomatis untuk sistem absensi.

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Command ini menyinkronkan absensi lama agar masuk ke tahun ajaran yang sesuai.
Artisan::command('absensi:sinkron-rekap {--tanggal=}', function () {
    $result = AbsensiRekapSync::harian($this->option('tanggal') ?: null);
    $this->info($result['message']);
})->purpose('Sinkronkan absensi harian agar masuk ke rekap admin');

// Scheduler menjalankan sinkronisasi, finalisasi guru, dan pengingat mobile secara otomatis.
Schedule::command('absensi:sinkron-rekap')->dailyAt('23:55');
Schedule::command('attendance:finalize-teacher-status')->everyMinute()->withoutOverlapping();
Schedule::command('mobile:attendance-reminders')->everyMinute()->withoutOverlapping();

// Command ini mengirim pengingat absensi mapel dan pulang ke aplikasi mobile.
Artisan::command('mobile:attendance-reminders', function (MobileAttendanceReminderService $service) {
    $result = $service->run();
    $this->info("Pengingat mapel: {$result['mapel']}; pengingat pulang: {$result['pulang']}.");
})->purpose('Kirim pengingat FCM mapel aktif dan absen pulang ke siswa serta orang tua');

// Command ini dipakai untuk menguji token FCM orang tua tanpa menampilkan token penuh.
Artisan::command('fcm:test-parent {parent_id}', function () {
    $studentId = (int) $this->argument('parent_id');

    $query = DB::table('parent_fcm_tokens')->where('siswa_id', $studentId);
    if (Schema::hasColumn('parent_fcm_tokens', 'audience')) {
        $query->where('audience', 'orang_tua');
    }
    if (Schema::hasColumn('parent_fcm_tokens', 'is_active')) {
        $query->where('is_active', true);
    }

    $tokens = $query->pluck('token')->filter()->unique()->values();
    if ($tokens->isEmpty()) {
        $this->warn('Tidak ada token FCM orang tua aktif untuk siswa/orang tua ini.');

        return 1;
    }

    $accessToken = fcmAccessToken();
    $projectId = fcmProjectId();
    if (! $accessToken || ! $projectId) {
        $this->error('Konfigurasi FCM belum lengkap. Periksa FCM_SERVICE_ACCOUNT_PATH dan project_id service account.');

        return 1;
    }

    $success = 0;
    $failed = 0;
    foreach ($tokens as $token) {
        $response = Http::withToken($accessToken)->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => 'Tes Notifikasi Orang Tua',
                    'body' => 'Jika pesan ini tampil, FCM orang tua sudah terhubung.',
                ],
                'data' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'siswa_id' => (string) $studentId,
                    'tipe' => 'test_parent',
                ],
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => 'absensi_sekolah',
                        'sound' => 'default',
                    ],
                ],
            ],
        ]);

        if ($response->successful()) {
            $success++;
            $this->line('Terkirim: token ...'.fcmTokenSuffix($token).' message_id='.$response->json('name'));
        } else {
            $failed++;
            $errorCode = fcmErrorCode($response->json());
            fcmMarkTokenFailure($token, $errorCode, $response->body());
            $this->warn('Gagal: token ...'.fcmTokenSuffix($token).' status='.$response->status().' error='.($errorCode ?: '-'));
        }
    }

    $this->info("Selesai. Berhasil={$success}; Gagal={$failed}; Total=".$tokens->count().'.');

    return $failed === 0 ? 0 : 1;
})->purpose('Kirim notifikasi uji FCM ke token aktif orang tua tanpa mencetak token penuh');

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
