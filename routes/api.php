<?php

use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobilePiketController;
use App\Http\Controllers\Api\MobileRoleController;
use App\Http\Controllers\Api\MobileWaliController;
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
| SCAN ABSENSI LAMA
|--------------------------------------------------------------------------
*/
Route::post('/scan', [AbsensiController::class, 'scan'])
    ->middleware('role:siswa');

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

Route::get('/mobile/role-context/{user_id}', [MobileRoleController::class, 'context']);

Route::get('/mobile/piket-dashboard/{user_id}', [MobilePiketController::class, 'dashboard']);

Route::get('/mobile/wali-dashboard/{user_id}', [MobileWaliController::class, 'dashboard']);

Route::get('/mobile/piket-absensi/{user_id}', [MobilePiketController::class, 'absensi']);

Route::get('/mobile/piket-riwayat/{user_id}', [MobilePiketController::class, 'riwayat']);

Route::get('/mobile/piket-jadwal/{user_id}', [MobilePiketController::class, 'jadwal']);

Route::get('/mobile/piket-pengajuan/{user_id}', [MobilePiketController::class, 'pengajuan']);

Route::post('/mobile/piket-pengajuan/{id}/review', [MobilePiketController::class, 'reviewPengajuan']);

Route::post('/siswa/pengajuan-izin', [SiswaPengajuanIzinController::class, 'store']);
