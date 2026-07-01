<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScanAbsensiController;
use App\Http\Controllers\Api\ScanMapelController;
use App\Http\Controllers\Api\SiswaDashboardController;
use App\Http\Controllers\Api\ParentFcmController;
use App\Http\Controllers\Api\SiswaKalenderController;
use App\Http\Controllers\Api\SiswaPengajuanIzinController;
use App\Http\Controllers\Api\SiswaRiwayatController;
use Illuminate\Support\Facades\Route;

require_once app_path('Support/api_helpers.php');

// Endpoint ini dipakai aplikasi Android untuk login siswa atau orang tua.
// Setelah login berhasil, API akan mengirim token yang dipakai untuk request berikutnya.
Route::post('/login', [AuthController::class, 'login']);

// Grup endpoint khusus siswa. Middleware role memastikan token yang dipakai benar milik siswa,
// sedangkan throttle membatasi jumlah request supaya scan tidak dikirim terlalu sering.
Route::middleware(['role:siswa', 'throttle:30,1'])->group(function () {
    // Scan QR untuk absensi harian, yaitu absen masuk dan absen pulang.
    Route::post('/scan-absensi', [ScanAbsensiController::class, 'store']);

    // Scan QR untuk absensi mata pelajaran sesuai jadwal dan Jam Pelajaran (JP).
    Route::post('/scan-mapel', [ScanMapelController::class, 'store']);

    // Pengajuan izin atau sakit dari siswa melalui aplikasi mobile.
    Route::post('/siswa/pengajuan-izin', [SiswaPengajuanIzinController::class, 'store']);
});

// Grup endpoint yang boleh diakses siswa dan orang tua.
// Orang tua tetap memakai data siswa, tetapi role konteksnya adalah orang_tua.
Route::middleware('role:siswa,orang_tua')->group(function () {
    // Menyimpan token perangkat agar aplikasi bisa menerima notifikasi FCM.
    Route::post('/fcm/register-device', [ParentFcmController::class, 'register']);
    Route::post('/fcm/register-parent', [ParentFcmController::class, 'register']);

    // Data riwayat, dashboard, dan kalender siswa untuk aplikasi Android.
    Route::get('/riwayat/{siswa_id}', [SiswaRiwayatController::class, 'index']);
    Route::get('/siswa/dashboard/{siswa_id}', [SiswaDashboardController::class, 'index']);
    Route::get('/siswa/kalender/{siswa_id}', [SiswaKalenderController::class, 'index']);
});
