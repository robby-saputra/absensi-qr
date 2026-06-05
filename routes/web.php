<?php

use App\Http\Controllers\Absensi\AbsensiNavigasiController;
use App\Http\Controllers\Admin\AbsensiAdminController;
use App\Http\Controllers\Admin\AdminFeatureController;
use App\Http\Controllers\Admin\AdminPdfController;
use App\Http\Controllers\Admin\AdminUtilityController;
use App\Http\Controllers\Admin\AutoAlfaController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\GuruPiketController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KalenderSekolahController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\NotifikasiSettingController;
use App\Http\Controllers\Admin\PengajuanIzinController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\RekapAdminController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WaliKelasController as AdminWaliKelasController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\GuruActionController;
use App\Http\Controllers\Dashboard\GuruDashboardController;
use App\Http\Controllers\Dashboard\PiketDashboardController;
use App\Http\Controllers\Dashboard\RoleReportController;
use App\Http\Controllers\Dashboard\SiswaDashboardController;
use App\Http\Controllers\Dashboard\WaliKelasDashboardController;
use App\Http\Controllers\Qr\QrViewController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\BantuanController;
use App\Http\Controllers\Web\HeartbeatController;
use App\Http\Controllers\Web\HomeRedirectController;
use App\Http\Controllers\Web\ManualAbsensiController;
use App\Http\Controllers\Web\NotifikasiSayaController;
use Illuminate\Support\Facades\Route;

require_once app_path('Support/web_helpers.php');

/*
|--------------------------------------------------------------------------
| Auth dan Bantuan
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeRedirectController::class, 'index']);
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::get('/logout', [AuthWebController::class, 'logout']);
Route::get('/heartbeat', HeartbeatController::class);

Route::get('/bantuan', [BantuanController::class, 'public']);
Route::get('/dashboard/bantuan', [BantuanController::class, 'dashboard'])
    ->middleware('webrole:admin,guru,piket,siswa');
Route::get('/dashboard/notifikasi-saya', [NotifikasiSayaController::class, 'index'])
    ->middleware('webrole:guru,piket,siswa');

/*
|--------------------------------------------------------------------------
| Dashboard Admin
|--------------------------------------------------------------------------
*/

Route::middleware('webrole:admin')->prefix('dashboard/admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard.admin');
    Route::get('/online-users', [AdminUtilityController::class, 'onlineUsers']);
    Route::post('/bulk-delete', [AdminUtilityController::class, 'bulkDelete']);
    Route::get('/notifikasi', [AdminUtilityController::class, 'notifikasi']);
    Route::post('/notifikasi/baca', [AdminUtilityController::class, 'bacaNotifikasi']);
    Route::get('/notifikasi-setting', [NotifikasiSettingController::class, 'index']);
    Route::post('/notifikasi-setting', [NotifikasiSettingController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Absensi
    |--------------------------------------------------------------------------
    */

    Route::get('/pengaturan', [PengaturanController::class, 'index']);
    Route::post('/pengaturan', [PengaturanController::class, 'store']);

    Route::get('/tahun-ajaran', [TahunAjaranController::class, 'index']);
    Route::get('/tahun-ajaran/create', [TahunAjaranController::class, 'create']);
    Route::post('/tahun-ajaran/store', [TahunAjaranController::class, 'store']);
    Route::get('/tahun-ajaran/edit/{id}', [TahunAjaranController::class, 'edit'])->whereNumber('id');
    Route::post('/tahun-ajaran/update/{id}', [TahunAjaranController::class, 'update'])->whereNumber('id');
    Route::post('/tahun-ajaran/{id}/aktif', [TahunAjaranController::class, 'aktif'])->whereNumber('id');
    Route::get('/tahun-ajaran/delete/{id}', [TahunAjaranController::class, 'delete'])->whereNumber('id');

    Route::get('/kalender-sekolah', [KalenderSekolahController::class, 'index']);
    Route::get('/kalender-sekolah/create', [KalenderSekolahController::class, 'create']);
    Route::post('/kalender-sekolah/store', [KalenderSekolahController::class, 'store']);
    Route::get('/kalender-sekolah/edit/{id}', [KalenderSekolahController::class, 'edit'])->whereNumber('id');
    Route::post('/kalender-sekolah/update/{id}', [KalenderSekolahController::class, 'update'])->whereNumber('id');
    Route::get('/kalender-sekolah/delete/{id}', [KalenderSekolahController::class, 'delete'])->whereNumber('id');
    Route::post('/kalender-sekolah/auto-nasional', [KalenderSekolahController::class, 'autoNasional']);
    Route::get('/kalender-sekolah/template', [AdminFeatureController::class, 'downloadTemplateKalender']);
    Route::post('/kalender-sekolah/import', [AdminFeatureController::class, 'importKalender']);
    Route::get('/kalender-sekolah/export', [AdminFeatureController::class, 'exportKalender']);

    /*
    |--------------------------------------------------------------------------
    | Data Master
    |--------------------------------------------------------------------------
    */

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/create', [UserController::class, 'create']);
    Route::post('/users/store', [UserController::class, 'store']);
    Route::get('/users/edit/{id}', [UserController::class, 'edit'])->whereNumber('id');
    Route::post('/users/update/{id}', [UserController::class, 'update'])->whereNumber('id');
    Route::get('/users/delete/{id}', [UserController::class, 'delete'])->whereNumber('id');
    Route::get('/users/{id}/reset-password', [AdminFeatureController::class, 'resetPasswordForm'])->whereNumber('id');
    Route::post('/users/{id}/reset-password', [AdminFeatureController::class, 'resetPassword'])->whereNumber('id');
    Route::post('/users/{id}/toggle-active', [AdminFeatureController::class, 'toggleActive'])->whereNumber('id');

    Route::get('/siswa', [SiswaController::class, 'index']);
    Route::get('/siswa/create', [SiswaController::class, 'create']);
    Route::post('/siswa/store', [SiswaController::class, 'store']);
    Route::get('/siswa/edit/{id}', [SiswaController::class, 'edit'])->whereNumber('id');
    Route::post('/siswa/update/{id}', [SiswaController::class, 'update'])->whereNumber('id');
    Route::get('/siswa/delete/{id}', [SiswaController::class, 'delete'])->whereNumber('id');
    Route::get('/siswa/detail/{id}', [SiswaController::class, 'detail'])->whereNumber('id');
    Route::get('/siswa/detail/{id}/pdf', [AdminPdfController::class, 'detailSiswa'])->whereNumber('id');
    Route::get('/siswa/import', [AdminFeatureController::class, 'importSiswaForm']);
    Route::post('/siswa/import', [AdminFeatureController::class, 'importSiswa']);
    Route::get('/siswa/template', [AdminFeatureController::class, 'downloadTemplateSiswa']);

    Route::get('/guru', [GuruController::class, 'index']);
    Route::get('/guru/create', [GuruController::class, 'create']);
    Route::post('/guru/store', [GuruController::class, 'store']);
    Route::get('/guru/delete/{id}', [GuruController::class, 'delete'])->whereNumber('id');
    Route::get('/guru/edit/{id}', [AdminFeatureController::class, 'editGuru'])->whereNumber('id');
    Route::post('/guru/update/{id}', [AdminFeatureController::class, 'updateGuru'])->whereNumber('id');

    Route::get('/kelas', [KelasController::class, 'index']);
    Route::get('/kelas/create', [KelasController::class, 'create']);
    Route::post('/kelas/store', [KelasController::class, 'store']);
    Route::get('/kelas/delete/{id}', [KelasController::class, 'delete'])->whereNumber('id');
    Route::get('/kelas/edit/{id}', [AdminFeatureController::class, 'editKelas'])->whereNumber('id');
    Route::post('/kelas/update/{id}', [AdminFeatureController::class, 'updateKelas'])->whereNumber('id');

    Route::get('/jurusan', [JurusanController::class, 'index']);
    Route::get('/jurusan/create', [JurusanController::class, 'create']);
    Route::post('/jurusan/store', [JurusanController::class, 'store']);
    Route::get('/jurusan/delete/{id}', [JurusanController::class, 'delete'])->whereNumber('id');
    Route::get('/jurusan/edit/{id}', [AdminFeatureController::class, 'editJurusan'])->whereNumber('id');
    Route::post('/jurusan/update/{id}', [AdminFeatureController::class, 'updateJurusan'])->whereNumber('id');

    Route::get('/wali-kelas', [AdminWaliKelasController::class, 'index']);
    Route::get('/wali-kelas/create', [AdminWaliKelasController::class, 'create']);
    Route::post('/wali-kelas/store', [AdminWaliKelasController::class, 'store']);
    Route::get('/wali-kelas/edit/{id}', [AdminWaliKelasController::class, 'edit'])->whereNumber('id');
    Route::post('/wali-kelas/update/{id}', [AdminWaliKelasController::class, 'update'])->whereNumber('id');
    Route::get('/wali-kelas/delete/{id}', [AdminWaliKelasController::class, 'delete'])->whereNumber('id');

    Route::get('/guru-piket', [GuruPiketController::class, 'index']);
    Route::get('/guru-piket/create', [GuruPiketController::class, 'create']);
    Route::get('/guru-piket/edit/{id}', [GuruPiketController::class, 'edit'])->whereNumber('id');
    Route::post('/guru-piket/store', [GuruPiketController::class, 'store']);
    Route::post('/guru-piket/update/{id}', [GuruPiketController::class, 'update'])->whereNumber('id');
    Route::get('/guru-piket/delete/{id}', [GuruPiketController::class, 'delete'])->whereNumber('id');

    Route::get('/jadwal', [JadwalController::class, 'index']);
    Route::get('/jadwal/create', [JadwalController::class, 'create']);
    Route::get('/jadwal/bentrok', [JadwalController::class, 'bentrok']);
    Route::get('/jadwal/edit/{id}', [JadwalController::class, 'edit'])->whereNumber('id');
    Route::post('/jadwal/store', [JadwalController::class, 'store']);
    Route::post('/jadwal/update/{id}', [JadwalController::class, 'update'])->whereNumber('id');
    Route::get('/jadwal/delete/{id}', [JadwalController::class, 'delete'])->whereNumber('id');
    Route::get('/jadwal/import', [AdminFeatureController::class, 'importJadwalForm']);
    Route::post('/jadwal/import', [AdminFeatureController::class, 'importJadwal']);
    Route::get('/jadwal/template', [AdminFeatureController::class, 'downloadTemplateJadwal']);

    /*
    |--------------------------------------------------------------------------
    | Absensi dan Rekap
    |--------------------------------------------------------------------------
    */

    Route::get('/absensi', [AbsensiAdminController::class, 'index']);
    Route::get('/absensi/create', [AbsensiAdminController::class, 'create']);
    Route::post('/absensi/store', [AbsensiAdminController::class, 'store']);
    Route::get('/absensi/{id}', [AbsensiAdminController::class, 'show'])->whereNumber('id');
    Route::get('/absensi/edit/{id}', [AbsensiAdminController::class, 'edit'])->whereNumber('id');
    Route::post('/absensi/update/{id}', [AbsensiAdminController::class, 'update'])->whereNumber('id');
    Route::get('/absensi/delete/{id}', [AbsensiAdminController::class, 'delete'])->whereNumber('id');
    Route::post('/absensi/sinkron-rekap', [AbsensiAdminController::class, 'sinkronRekap']);
    Route::get('/absensi/rekap', [AdminFeatureController::class, 'rekapAbsensi'])->name('rekap.absensi');
    Route::get('/absensi/export', [AdminFeatureController::class, 'exportAbsensi'])->name('export.absensi');

    Route::get('/absensi-mapel', [AbsensiAdminController::class, 'mapelIndex']);
    Route::get('/absensi-mapel/create', [AbsensiAdminController::class, 'mapelCreate']);
    Route::post('/absensi-mapel/store', [AbsensiAdminController::class, 'mapelStore']);
    Route::get('/absensi-mapel/{id}', [AbsensiAdminController::class, 'mapelShow'])->whereNumber('id');
    Route::get('/absensi-mapel/edit/{id}', [AbsensiAdminController::class, 'mapelEdit'])->whereNumber('id');
    Route::post('/absensi-mapel/update/{id}', [AbsensiAdminController::class, 'mapelUpdate'])->whereNumber('id');
    Route::get('/absensi-mapel/delete/{id}', [AbsensiAdminController::class, 'mapelDelete'])->whereNumber('id');

    Route::get('/pengajuan-izin', [PengajuanIzinController::class, 'index']);
    Route::post('/pengajuan-izin/{id}/review', [PengajuanIzinController::class, 'review'])->whereNumber('id');
    Route::post('/auto-alfa', [AutoAlfaController::class, 'store']);

    Route::get('/rekap/guru-piket', [RekapAdminController::class, 'guruPiket']);
    Route::get('/rekap/absensi-mapel', [RekapAdminController::class, 'absensiMapel']);
    Route::get('/rekap/jadwal-guru-mapel', [RekapAdminController::class, 'jadwalGuruMapel']);

    Route::get('/rekap/absensi-pdf', [AdminPdfController::class, 'absensiHarian']);
    Route::get('/rekap/absensi-mapel-pdf', [AdminPdfController::class, 'absensiMapel']);
    Route::get('/rekap/guru-piket-pdf', [AdminPdfController::class, 'guruPiket']);
    Route::get('/rekap/jadwal-guru-mapel-pdf', [AdminPdfController::class, 'jadwalGuruMapel']);
    Route::get('/rekap/wali-kelas-pdf', [AdminPdfController::class, 'waliKelas']);

    Route::get('/pdf/{type}', [AdminPdfController::class, 'admin'])
        ->whereIn('type', [
            'siswa',
            'guru',
            'wali-kelas',
            'guru-piket',
            'kelas',
            'jurusan',
            'tahun-ajaran',
            'kalender',
            'jadwal',
            'pengajuan-izin',
            'absensi-harian-crud',
            'absensi-mapel',
            'absensi-mapel-crud',
        ]);
});

/*
|--------------------------------------------------------------------------
| Dashboard Guru
|--------------------------------------------------------------------------
*/

Route::middleware('webrole:guru')->prefix('dashboard/guru')->group(function () {
    Route::get('/', [GuruDashboardController::class, 'index']);
    Route::get('/jadwal', [GuruDashboardController::class, 'jadwal']);
    Route::get('/verifikasi-absensi', [GuruDashboardController::class, 'verifikasiAbsensi']);
    Route::get('/riwayat-absensi', [GuruDashboardController::class, 'riwayatAbsensi']);
    Route::get('/rekap-siswa', [GuruDashboardController::class, 'rekapSiswa']);
    Route::get('/rekap-absensi', [GuruDashboardController::class, 'rekapAbsensi']);
    Route::get('/rekap-absensi-mapel', [AbsensiNavigasiController::class, 'guruRekapAbsensiMapel']);
    Route::get('/rekap-jadwal', [GuruDashboardController::class, 'rekapJadwal']);
    Route::get('/pengajuan-izin', [GuruActionController::class, 'pengajuanIzin']);
    Route::get('/laporan-bulanan', [RoleReportController::class, 'guruLaporanBulanan']);
    Route::get('/pdf/{type}', [RoleReportController::class, 'guruPdf']);

    Route::get('/absensi/{siswaId}/view', [GuruActionController::class, 'viewAbsensi'])->whereNumber('siswaId');
    Route::get('/absensi/{siswaId}/edit', [GuruActionController::class, 'editAbsensi'])->whereNumber('siswaId');
    Route::post('/absensi/{siswaId}/update', [GuruActionController::class, 'updateAbsensi'])->whereNumber('siswaId');

    Route::get('/absensi-mapel/{jadwalId}/{siswaId}/view', [GuruActionController::class, 'viewAbsensiMapel'])
        ->whereNumber('jadwalId')
        ->whereNumber('siswaId');
    Route::get('/absensi-mapel/{jadwalId}/{siswaId}/edit', [GuruActionController::class, 'editAbsensiMapel'])
        ->whereNumber('jadwalId')
        ->whereNumber('siswaId');
    Route::post('/absensi-mapel/{jadwalId}/{siswaId}/update', [GuruActionController::class, 'updateAbsensiMapel'])
        ->whereNumber('jadwalId')
        ->whereNumber('siswaId');

    Route::get('/mulai-sesi/{jadwalId}', [GuruActionController::class, 'mulaiSesi'])->whereNumber('jadwalId');
    Route::get('/qr/{id}/view', [QrViewController::class, 'guruView'])->whereNumber('id');
});

/*
|--------------------------------------------------------------------------
| Dashboard Piket
|--------------------------------------------------------------------------
*/

Route::middleware('webrole:piket,guru')->prefix('dashboard/piket')->group(function () {
    Route::get('/', [PiketDashboardController::class, 'index']);
    Route::get('/absensi-harian', [AbsensiNavigasiController::class, 'piketAbsensiHarian']);
    Route::get('/riwayat-absensi', [AbsensiNavigasiController::class, 'piketRiwayatAbsensi']);
    Route::get('/rekap-jadwal', [GuruDashboardController::class, 'piketRekapJadwal']);
    Route::get('/pengajuan-izin', [PiketDashboardController::class, 'pengajuanIzin']);
    Route::post('/pengajuan-izin/{id}/review', [PiketDashboardController::class, 'reviewPengajuanIzin'])->whereNumber('id');
    Route::get('/laporan-bulanan', [RoleReportController::class, 'piketLaporanBulanan']);
    Route::get('/pdf/{type}', [RoleReportController::class, 'piketPdf']);

    Route::get('/qr-harian', [QrViewController::class, 'piketQrHarian']);
    Route::get('/qr/{id}/view', [QrViewController::class, 'piketView'])->whereNumber('id');
    Route::post('/generate-qr', [PiketDashboardController::class, 'generateQr']);
    Route::post('/status', [PiketDashboardController::class, 'status']);

    Route::get('/absensi/{siswaId}/view', [PiketDashboardController::class, 'viewAbsensi'])->whereNumber('siswaId');
    Route::get('/absensi/{siswaId}/edit', [PiketDashboardController::class, 'editAbsensi'])->whereNumber('siswaId');
    Route::post('/absensi/{siswaId}/update', [PiketDashboardController::class, 'updateAbsensi'])->whereNumber('siswaId');
});

/*
|--------------------------------------------------------------------------
| Dashboard Wali Kelas
|--------------------------------------------------------------------------
*/

Route::middleware('webrole:guru')->prefix('dashboard/wali')->group(function () {
    Route::get('/', [WaliKelasDashboardController::class, 'index']);
    Route::get('/siswa', [WaliKelasDashboardController::class, 'siswa']);
    Route::get('/siswa/detail/{id}', [WaliKelasDashboardController::class, 'detailSiswa'])->whereNumber('id');
    Route::get('/absensi', [WaliKelasDashboardController::class, 'absensi']);
    Route::get('/laporan-bulanan', [RoleReportController::class, 'waliLaporanBulanan']);
    Route::get('/pdf/{type}', [RoleReportController::class, 'waliPdf']);
    Route::get('/surat/{siswaId}', [RoleReportController::class, 'waliSurat'])->whereNumber('siswaId');
});

/*
|--------------------------------------------------------------------------
| Dashboard Siswa Web
|--------------------------------------------------------------------------
*/

Route::get('/dashboard/users', [SiswaDashboardController::class, 'index'])->middleware('webrole:siswa');
Route::post('/dashboard/users/izin/store', [SiswaDashboardController::class, 'storeIzin'])->middleware('webrole:siswa');

Route::post('/absensi/manual', [ManualAbsensiController::class, 'store']);
