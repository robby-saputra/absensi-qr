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

Route::post('/login', [AuthController::class, 'login']);

Route::post('/fcm/register-parent', [ParentFcmController::class, 'register']);

Route::post('/scan-absensi', [ScanAbsensiController::class, 'store']);

Route::post('/scan-mapel', [ScanMapelController::class, 'store']);

Route::get('/riwayat/{siswa_id}', [SiswaRiwayatController::class, 'index']);

Route::get('/siswa/dashboard/{siswa_id}', [SiswaDashboardController::class, 'index']);

Route::get('/siswa/kalender/{siswa_id}', [SiswaKalenderController::class, 'index']);

Route::post('/siswa/pengajuan-izin', [SiswaPengajuanIzinController::class, 'store']);
