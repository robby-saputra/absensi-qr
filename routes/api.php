<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScanAbsensiController;
use App\Http\Controllers\Api\ScanMapelController;
use App\Http\Controllers\Api\SiswaDashboardController;
use App\Http\Controllers\Api\ParentFcmController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\SiswaKalenderController;
use App\Http\Controllers\Api\SiswaPengajuanIzinController;
use App\Http\Controllers\Api\SiswaRiwayatController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Support\Facades\Route;

require_once app_path('Support/api_helpers.php');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/fcm/register-parent', [ParentFcmController::class, 'register']);

/*
|--------------------------------------------------------------------------
| QR GENERATOR (PIKET ONLY)
|--------------------------------------------------------------------------
*/
Route::post('/qr/generate', [QrController::class, 'generate'])
    ->middleware('role:piket,guru');

/*
|--------------------------------------------------------------------------
| DATA USERS (PIKET ONLY)
|--------------------------------------------------------------------------
*/
Route::get('/users', [UsersController::class, 'index'])
    ->middleware('role:piket');

/*
|--------------------------------------------------------------------------
| SCAN ABSENSI FLUTTER
|--------------------------------------------------------------------------
*/
Route::post('/scan-absensi', [ScanAbsensiController::class, 'store']);

Route::post('/scan-mapel', [ScanMapelController::class, 'store']);

Route::get('/riwayat/{siswa_id}', [SiswaRiwayatController::class, 'index']);

Route::get('/siswa/dashboard/{siswa_id}', [SiswaDashboardController::class, 'index']);

Route::get('/siswa/kalender/{siswa_id}', [SiswaKalenderController::class, 'index']);

Route::post('/siswa/pengajuan-izin', [SiswaPengajuanIzinController::class, 'store']);
