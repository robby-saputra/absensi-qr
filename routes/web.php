<?php

use App\Http\Controllers\Admin\AdminFeatureController;
use App\Http\Controllers\Admin\AbsensiAdminController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\GuruPiketController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\KalenderSekolahController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ArsipController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\KeamananController;
use App\Http\Controllers\Admin\KesehatanDataController;
use App\Http\Controllers\Admin\NotifikasiSettingController;
use App\Http\Controllers\Admin\PengajuanIzinController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PengumumanController as AdminPengumumanController;
use App\Http\Controllers\Admin\AutoAlfaController;
use App\Http\Controllers\Admin\AdminPdfController;
use App\Http\Controllers\Admin\RekapAdminController;
use App\Http\Controllers\Admin\RoleAksesController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WaliKelasController as AdminWaliKelasController;
use App\Http\Controllers\Absensi\AbsensiNavigasiController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\BantuanController;
use App\Http\Controllers\Web\HomeRedirectController;
use App\Http\Controllers\Web\NotifikasiSayaController;
use App\Http\Controllers\Web\PengumumanController;
use App\Http\Controllers\Web\RiwayatPerubahanController;
use App\Http\Controllers\Web\HeartbeatController;
use App\Http\Controllers\Web\RoleCommunicationController;
use App\Http\Controllers\Web\ManualAbsensiController;
use App\Http\Controllers\Admin\AdminUtilityController;
use App\Http\Controllers\Dashboard\GuruDashboardController;
use App\Http\Controllers\Dashboard\GuruActionController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\PiketDashboardController;
use App\Http\Controllers\Dashboard\RoleReportController;
use App\Http\Controllers\Dashboard\SiswaDashboardController;
use App\Http\Controllers\Dashboard\WaliKelasDashboardController;
use App\Http\Controllers\Qr\QrViewController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeRedirectController::class, 'index']);
require_once app_path('Support/web_helpers.php');

Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::get('/logout', [AuthWebController::class, 'logout']);
Route::get('/heartbeat', HeartbeatController::class);

Route::get('/dashboard/pengumuman', [PengumumanController::class, 'public'])->middleware('webrole:guru,piket');
Route::get('/dashboard/bantuan', [BantuanController::class, 'dashboard'])->middleware('webrole:admin,guru,piket,siswa');
Route::get('/bantuan', [BantuanController::class, 'public']);
Route::get('/dashboard/notifikasi-saya', [NotifikasiSayaController::class, 'index'])->middleware('webrole:guru,piket,siswa');
Route::get('/dashboard/riwayat-perubahan-saya', [RiwayatPerubahanController::class, 'index'])->middleware('webrole:guru,piket');

Route::get('/dashboard/pesan-internal', [RoleCommunicationController::class, 'pesanIndex'])->middleware('webrole:guru,piket');
Route::post('/dashboard/pesan-internal', [RoleCommunicationController::class, 'pesanStore'])->middleware('webrole:guru,piket');

Route::get('/dashboard/admin', [AdminDashboardController::class, 'index'])->middleware('webrole:admin')->name('dashboard.admin');

Route::get('/dashboard/admin/online-users', [AdminUtilityController::class, 'onlineUsers'])->middleware('webrole:admin');
Route::post('/dashboard/admin/bulk-delete', [AdminUtilityController::class, 'bulkDelete'])->middleware('webrole:admin');
Route::get('/dashboard/admin/notifikasi', [AdminUtilityController::class, 'notifikasi'])->middleware('webrole:admin');
Route::post('/dashboard/admin/notifikasi/baca', [AdminUtilityController::class, 'bacaNotifikasi'])->middleware('webrole:admin');

Route::middleware('webrole:admin')->group(function () {
    Route::get('/dashboard/admin/kalender-sekolah', [KalenderSekolahController::class, 'index']);

    Route::get('/dashboard/admin/kalender-sekolah/create', [KalenderSekolahController::class, 'create']);

    Route::post('/dashboard/admin/kalender-sekolah/store', [KalenderSekolahController::class, 'store']);

    Route::get('/dashboard/admin/kalender-sekolah/edit/{id}', [KalenderSekolahController::class, 'edit']);

    Route::post('/dashboard/admin/kalender-sekolah/update/{id}', [KalenderSekolahController::class, 'update']);

    Route::get('/dashboard/admin/kalender-sekolah/delete/{id}', [KalenderSekolahController::class, 'delete']);

    Route::post('/dashboard/admin/kalender-sekolah/auto-nasional', [KalenderSekolahController::class, 'autoNasional']);

    Route::get('/dashboard/admin/pengaturan', [PengaturanController::class, 'index']);

    Route::post('/dashboard/admin/pengaturan', [PengaturanController::class, 'store']);

    Route::get('/dashboard/admin/audit-log', [AuditLogController::class, 'index']);

    Route::get('/dashboard/admin/audit-log/{id}', [AuditLogController::class, 'show'])->whereNumber('id');

    Route::get('/dashboard/admin/backup', [BackupController::class, 'index']);

    Route::post('/dashboard/admin/backup/create', [BackupController::class, 'create']);

    Route::post('/dashboard/admin/backup/create-sql', [BackupController::class, 'createSql']);

    Route::get('/dashboard/admin/backup/download/{file}', [BackupController::class, 'download']);

    Route::post('/dashboard/admin/backup/restore/{file}', [BackupController::class, 'restore']);

    Route::get('/dashboard/admin/arsip', [ArsipController::class, 'index']);

    Route::get('/dashboard/admin/arsip/preview', [ArsipController::class, 'preview']);

    Route::post('/dashboard/admin/arsip/restore', [ArsipController::class, 'restore']);

    Route::post('/dashboard/admin/arsip/bulk-restore', [ArsipController::class, 'bulkRestore']);

    Route::post('/dashboard/admin/arsip/force-delete', [ArsipController::class, 'forceDelete']);

    Route::post('/dashboard/admin/arsip/bulk-force-delete', [ArsipController::class, 'bulkForceDelete']);

    Route::get('/dashboard/admin/keamanan', [KeamananController::class, 'index']);

    Route::get('/dashboard/admin/kesehatan-data', [KesehatanDataController::class, 'index']);

    Route::get('/dashboard/admin/role-akses', [RoleAksesController::class, 'index']);

    Route::get('/dashboard/admin/notifikasi-setting', [NotifikasiSettingController::class, 'index']);

    Route::post('/dashboard/admin/notifikasi-setting', [NotifikasiSettingController::class, 'store']);

    Route::get('/dashboard/admin/pengumuman', [AdminPengumumanController::class, 'index']);

    Route::get('/dashboard/admin/pengumuman/create', [AdminPengumumanController::class, 'create']);

    Route::post('/dashboard/admin/pengumuman/store', [AdminPengumumanController::class, 'store']);

    Route::get('/dashboard/admin/pengumuman/edit/{id}', [AdminPengumumanController::class, 'edit'])->whereNumber('id');

    Route::post('/dashboard/admin/pengumuman/update/{id}', [AdminPengumumanController::class, 'update'])->whereNumber('id');

    Route::get('/dashboard/admin/pengumuman/delete/{id}', [AdminPengumumanController::class, 'delete'])->whereNumber('id');

    Route::get('/dashboard/admin/pengajuan-izin', [PengajuanIzinController::class, 'index']);

    Route::post('/dashboard/admin/pengajuan-izin/{id}/review', [PengajuanIzinController::class, 'review'])->whereNumber('id');

    Route::post('/dashboard/admin/auto-alfa', [AutoAlfaController::class, 'store']);

    Route::get('/dashboard/admin/rekap/absensi-pdf', [AdminPdfController::class, 'absensiHarian']);

    Route::get('/dashboard/admin/rekap/absensi-mapel-pdf', [AdminPdfController::class, 'absensiMapel']);

    Route::get('/dashboard/admin/rekap/guru-piket-pdf', [AdminPdfController::class, 'guruPiket']);

    Route::get('/dashboard/admin/rekap/jadwal-guru-mapel-pdf', [AdminPdfController::class, 'jadwalGuruMapel']);

    Route::get('/dashboard/admin/rekap/wali-kelas-pdf', [AdminPdfController::class, 'waliKelas']);

    Route::get('/dashboard/admin/pdf/{type}', [AdminPdfController::class, 'admin']);

    Route::get('/dashboard/admin/siswa/detail/{id}/pdf', [AdminPdfController::class, 'detailSiswa'])->whereNumber('id');

    Route::get('/dashboard/admin/tahun-ajaran', [TahunAjaranController::class, 'index']);

    Route::get('/dashboard/admin/tahun-ajaran/create', [TahunAjaranController::class, 'create']);

    Route::post('/dashboard/admin/tahun-ajaran/store', [TahunAjaranController::class, 'store']);

    Route::get('/dashboard/admin/tahun-ajaran/edit/{id}', [TahunAjaranController::class, 'edit']);

    Route::post('/dashboard/admin/tahun-ajaran/update/{id}', [TahunAjaranController::class, 'update']);

    Route::post('/dashboard/admin/tahun-ajaran/{id}/aktif', [TahunAjaranController::class, 'aktif']);

    Route::get('/dashboard/admin/tahun-ajaran/delete/{id}', [TahunAjaranController::class, 'delete']);

    Route::get('/dashboard/admin/rekap/guru-piket', [RekapAdminController::class, 'guruPiket']);

    Route::get('/dashboard/admin/rekap/absensi-mapel', [RekapAdminController::class, 'absensiMapel']);

    Route::get('/dashboard/admin/absensi', [AbsensiAdminController::class, 'index']);

    Route::get('/dashboard/admin/absensi/create', [AbsensiAdminController::class, 'create']);

    Route::post('/dashboard/admin/absensi/sinkron-rekap', [AbsensiAdminController::class, 'sinkronRekap']);

    Route::post('/dashboard/admin/absensi/store', [AbsensiAdminController::class, 'store']);

    Route::get('/dashboard/admin/absensi/{id}', [AbsensiAdminController::class, 'show'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi/edit/{id}', [AbsensiAdminController::class, 'edit'])->whereNumber('id');

    Route::post('/dashboard/admin/absensi/update/{id}', [AbsensiAdminController::class, 'update'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi/delete/{id}', [AbsensiAdminController::class, 'delete'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel', [AbsensiAdminController::class, 'mapelIndex']);

    Route::get('/dashboard/admin/absensi-mapel/create', [AbsensiAdminController::class, 'mapelCreate']);

    Route::post('/dashboard/admin/absensi-mapel/store', [AbsensiAdminController::class, 'mapelStore']);

    Route::get('/dashboard/admin/absensi-mapel/{id}', [AbsensiAdminController::class, 'mapelShow'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel/edit/{id}', [AbsensiAdminController::class, 'mapelEdit'])->whereNumber('id');

    Route::post('/dashboard/admin/absensi-mapel/update/{id}', [AbsensiAdminController::class, 'mapelUpdate'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel/delete/{id}', [AbsensiAdminController::class, 'mapelDelete'])->whereNumber('id');

    Route::get('/dashboard/admin/rekap/jadwal-guru-mapel', [RekapAdminController::class, 'jadwalGuruMapel']);
});

/*
|--------------------------------------------------------------------------
| FITUR TAMBAHAN ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware('webrole:admin')->group(function () {

    Route::get('/dashboard/admin/users', [UserController::class, 'index']);

    Route::get('/dashboard/admin/users/create', [UserController::class, 'create'])->whereNumber('id');

    Route::post('/dashboard/admin/users/store', [UserController::class, 'store']);

    Route::get('/dashboard/admin/users/edit/{id}', [UserController::class, 'edit']);

    Route::post('/dashboard/admin/users/update/{id}', [UserController::class, 'update'])->whereNumber('id');

    Route::get('/dashboard/admin/users/delete/{id}', [UserController::class, 'delete'])->whereNumber('id');

    Route::get(
        '/dashboard/admin/siswa/import',
        [AdminFeatureController::class, 'importSiswaForm']
    );

    Route::post(
        '/dashboard/admin/siswa/import',
        [AdminFeatureController::class, 'importSiswa']
    );

    Route::get(
        '/dashboard/admin/siswa/template',
        [AdminFeatureController::class, 'downloadTemplateSiswa',
        ]);

    Route::get(
        '/dashboard/admin/jadwal/import',
        [AdminFeatureController::class, 'importJadwalForm']
    );

    Route::post(
        '/dashboard/admin/jadwal/import',
        [AdminFeatureController::class, 'importJadwal']
    );

    Route::get(
        '/dashboard/admin/jadwal/template',
        [AdminFeatureController::class, 'downloadTemplateJadwal']
    );

    Route::get(
        '/dashboard/admin/kalender-sekolah/template',
        [AdminFeatureController::class, 'downloadTemplateKalender']
    );

    Route::post(
        '/dashboard/admin/kalender-sekolah/import',
        [AdminFeatureController::class, 'importKalender']
    );

    Route::get(
        '/dashboard/admin/kalender-sekolah/export',
        [AdminFeatureController::class, 'exportKalender']
    );

    /*
    |--------------------------------------------------------------------------
    | REKAP ABSENSI
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/absensi/rekap',
        [AdminFeatureController::class, 'rekapAbsensi']
    )
        ->name(
            'rekap.absensi'
        );

    /*
    |--------------------------------------------------------------------------
    | EXPORT EXCEL
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/absensi/export',
        [AdminFeatureController::class, 'exportAbsensi']
    )
        ->name(
            'export.absensi'
        );

    /*
    |--------------------------------------------------------------------------



    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD USER
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/users/{id}/reset-password',
        [AdminFeatureController::class, 'resetPasswordForm']
    );

    Route::post(
        '/dashboard/admin/users/{id}/reset-password',
        [AdminFeatureController::class, 'resetPassword']
    );

    Route::post(
        '/dashboard/admin/users/{id}/toggle-active',
        [AdminFeatureController::class, 'toggleActive']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT GURU
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/guru/edit/{id}',
        [AdminFeatureController::class, 'editGuru']
    );

    Route::post(
        '/dashboard/admin/guru/update/{id}',
        [AdminFeatureController::class, 'updateGuru']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT KELAS
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/kelas/edit/{id}',
        [AdminFeatureController::class, 'editKelas']
    );

    Route::post(
        '/dashboard/admin/kelas/update/{id}',
        [AdminFeatureController::class, 'updateKelas']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT JURUSAN
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/jurusan/edit/{id}',
        [AdminFeatureController::class, 'editJurusan']
    );

    Route::post(
        '/dashboard/admin/jurusan/update/{id}',
        [AdminFeatureController::class, 'updateJurusan']
    );

});

/*
|--------------------------------------------------------------------------
| LIST GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru', [GuruController::class, 'index'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM TAMBAH GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru/create', [GuruController::class, 'create'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN GURU
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/guru/store', [GuruController::class, 'store'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru/delete/{id}', [GuruController::class, 'delete'])->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| LIST KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas', [KelasController::class, 'index'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM TAMBAH KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas/create', [KelasController::class, 'create'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN KELAS
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/kelas/store', [KelasController::class, 'store'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas/delete/{id}', [KelasController::class, 'delete'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| LIST JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal', [JadwalController::class, 'index'])->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
| FORM TAMBAH JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal/create', [JadwalController::class, 'create'])->middleware('webrole:admin');

Route::get('/dashboard/admin/jadwal/bentrok', [JadwalController::class, 'bentrok'])->middleware('webrole:admin');

Route::get('/dashboard/admin/jadwal/edit/{id}', [JadwalController::class, 'edit'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
|/*
|--------------------------------------------------------------------------
| SIMPAN JADWAL
|--------------------------------------------------------------------------
*/

Route::post('/dashboard/admin/jadwal/store', [JadwalController::class, 'store'])->middleware('webrole:admin');

Route::post('/dashboard/admin/jadwal/update/{id}', [JadwalController::class, 'update'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal/delete/{id}', [JadwalController::class, 'delete'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| LIST SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa', [SiswaController::class, 'index'])->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| FORM TAMBAH SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/create', [SiswaController::class, 'create'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| SIMPAN SISWA
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/siswa/store', [SiswaController::class, 'store'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM EDIT SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/edit/{id}', [SiswaController::class, 'edit'])->middleware('webrole:admin');

Route::get('/dashboard/admin/siswa/detail/{id}', [SiswaController::class, 'detail'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| UPDATE SISWA
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/siswa/update/{id}', [SiswaController::class, 'update'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/delete/{id}', [SiswaController::class, 'delete'])->middleware('webrole:admin');
/*

/*
|--------------------------------------------------------------------------
| LIST WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas', [AdminWaliKelasController::class, 'index'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| FORM TAMBAH / SET WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/create', [AdminWaliKelasController::class, 'create'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN WALI KELAS
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/wali-kelas/store', [AdminWaliKelasController::class, 'store'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM EDIT WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/edit/{id}', [AdminWaliKelasController::class, 'edit'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| UPDATE WALI KELAS
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/wali-kelas/update/{id}', [AdminWaliKelasController::class, 'update'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/delete/{id}', [AdminWaliKelasController::class, 'delete'])->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
| DASHBOARD PIKET
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/piket', [PiketDashboardController::class, 'index'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/absensi-harian', [AbsensiNavigasiController::class, 'piketAbsensiHarian'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/riwayat-absensi', [AbsensiNavigasiController::class, 'piketRiwayatAbsensi'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/rekap-jadwal', [GuruDashboardController::class, 'piketRekapJadwal'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/qr-harian', [QrViewController::class, 'piketQrHarian'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/qr/{id}/view', [QrViewController::class, 'piketView'])->middleware('webrole:piket,guru')->whereNumber('id');

Route::get('/dashboard/piket/pengajuan-izin', [PiketDashboardController::class, 'pengajuanIzin'])->middleware('webrole:piket,guru');
Route::post('/dashboard/piket/pengajuan-izin/{id}/review', [PiketDashboardController::class, 'reviewPengajuanIzin'])->middleware('webrole:piket,guru')->whereNumber('id');
Route::get('/dashboard/piket/absensi/{siswaId}/view', [PiketDashboardController::class, 'viewAbsensi'])->middleware('webrole:piket,guru');
Route::get('/dashboard/piket/absensi/{siswaId}/edit', [PiketDashboardController::class, 'editAbsensi'])->middleware('webrole:piket,guru');
Route::post('/dashboard/piket/absensi/{siswaId}/update', [PiketDashboardController::class, 'updateAbsensi'])->middleware('webrole:piket,guru');

/*
|--------------------------------------------------------------------------
| GENERATE QR
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/piket/generate-qr', [PiketDashboardController::class, 'generateQr'])->middleware('webrole:piket,guru');
Route::post('/dashboard/piket/status', [PiketDashboardController::class, 'status'])->middleware('webrole:piket,guru');

/*
|--------------------------------------------------------------------------
| DASHBOARD WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali', [WaliKelasDashboardController::class, 'index'])->middleware('webrole:guru');
/*
|--------------------------------------------------------------------------
| DATA SISWA WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali/siswa', [WaliKelasDashboardController::class, 'siswa'])->middleware('webrole:guru');

Route::get('/dashboard/wali/siswa/detail/{id}', [WaliKelasDashboardController::class, 'detailSiswa'])->middleware('webrole:guru');

/*
|--------------------------------------------------------------------------
| ABSENSI SISWA WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali/absensi', [WaliKelasDashboardController::class, 'absensi'])->middleware('webrole:guru');

Route::post('/dashboard/wali/siswa/{id}/catatan', [WaliKelasDashboardController::class, 'simpanCatatan'])->middleware('webrole:guru')->whereNumber('id');

Route::get('/dashboard/guru/pdf/{type}', [RoleReportController::class, 'guruPdf'])->middleware('webrole:guru');
Route::get('/dashboard/piket/pdf/{type}', [RoleReportController::class, 'piketPdf'])->middleware('webrole:piket,guru');
Route::get('/dashboard/wali/pdf/{type}', [RoleReportController::class, 'waliPdf'])->middleware('webrole:guru');
Route::get('/dashboard/wali/surat/{siswaId}', [RoleReportController::class, 'waliSurat'])->middleware('webrole:guru')->whereNumber('siswaId');
Route::get('/dashboard/guru/laporan-bulanan', [RoleReportController::class, 'guruLaporanBulanan'])->middleware('webrole:guru');
Route::get('/dashboard/piket/laporan-bulanan', [RoleReportController::class, 'piketLaporanBulanan'])->middleware('webrole:piket,guru');
Route::get('/dashboard/wali/laporan-bulanan', [RoleReportController::class, 'waliLaporanBulanan'])->middleware('webrole:guru');
Route::get('/dashboard/validasi-tutup-bulan', [RoleReportController::class, 'validasiTutupBulan'])->middleware('webrole:admin,guru');
Route::post('/dashboard/validasi-tutup-bulan/kunci', [RoleReportController::class, 'kunciTutupBulan'])->middleware('webrole:admin,guru');
Route::get('/dashboard/guru', [GuruDashboardController::class, 'index'])->middleware('webrole:guru');

Route::get('/dashboard/guru/jadwal', [GuruDashboardController::class, 'jadwal'])->middleware('webrole:guru');

Route::get('/dashboard/guru/verifikasi-absensi', [GuruDashboardController::class, 'verifikasiAbsensi'])->middleware('webrole:guru');

Route::get('/dashboard/guru/riwayat-absensi', [GuruDashboardController::class, 'riwayatAbsensi'])->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-siswa', [GuruDashboardController::class, 'rekapSiswa'])->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-absensi', [GuruDashboardController::class, 'rekapAbsensi'])->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-absensi-mapel', [AbsensiNavigasiController::class, 'guruRekapAbsensiMapel'])->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-jadwal', [GuruDashboardController::class, 'rekapJadwal'])->middleware('webrole:guru');

Route::get('/dashboard/guru/absensi/{siswaId}/view', [GuruActionController::class, 'viewAbsensi'])->middleware('webrole:guru');
Route::get('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/view', [GuruActionController::class, 'viewAbsensiMapel'])->middleware('webrole:guru');
Route::get('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/edit', [GuruActionController::class, 'editAbsensiMapel'])->middleware('webrole:guru');
Route::post('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/update', [GuruActionController::class, 'updateAbsensiMapel'])->middleware('webrole:guru');
Route::get('/dashboard/guru/absensi/{siswaId}/edit', [GuruActionController::class, 'editAbsensi'])->middleware('webrole:guru');
Route::get('/dashboard/guru/pengajuan-izin', [GuruActionController::class, 'pengajuanIzin'])->middleware('webrole:guru');
Route::post('/dashboard/guru/absensi/{siswaId}/update', [GuruActionController::class, 'updateAbsensi'])->middleware('webrole:guru');

Route::post('/dashboard/guru/status/{id}', [GuruActionController::class, 'status'])->middleware('webrole:guru');
Route::get('/dashboard/guru/mulai-sesi/{jadwalId}', [GuruActionController::class, 'mulaiSesi'])->middleware('webrole:guru');

Route::get('/dashboard/guru/qr/{id}/view', [QrViewController::class, 'guruView'])->middleware('webrole:guru')->whereNumber('id');

Route::get('/dashboard/users', [SiswaDashboardController::class, 'index'])->middleware('webrole:siswa');
Route::post('/dashboard/users/izin/store', [SiswaDashboardController::class, 'storeIzin'])->middleware('webrole:siswa');

Route::post('/absensi/manual', [ManualAbsensiController::class, 'store']);

Route::middleware('webrole:admin')->group(function () {
    Route::get('/dashboard/admin/guru-piket', [GuruPiketController::class, 'index']);
    Route::get('/dashboard/admin/guru-piket/create', [GuruPiketController::class, 'create']);
    Route::get('/dashboard/admin/guru-piket/edit/{id}', [GuruPiketController::class, 'edit']);
    Route::post('/dashboard/admin/guru-piket/store', [GuruPiketController::class, 'store']);
    Route::post('/dashboard/admin/guru-piket/update/{id}', [GuruPiketController::class, 'update']);
    Route::get('/dashboard/admin/guru-piket/delete/{id}', [GuruPiketController::class, 'delete']);
    Route::get('/dashboard/admin/jurusan', [JurusanController::class, 'index']);
    Route::get('/dashboard/admin/jurusan/create', [JurusanController::class, 'create']);
    Route::post('/dashboard/admin/jurusan/store', [JurusanController::class, 'store']);
    Route::get('/dashboard/admin/jurusan/delete/{id}', [JurusanController::class, 'delete']);
});
