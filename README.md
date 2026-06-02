# Absensi QR

Absensi QR adalah sistem absensi sekolah berbasis Laravel untuk mengelola absensi harian, absensi mata pelajaran, QR Code, guru piket, guru mapel, wali kelas, siswa, orang tua, rekap, notifikasi, arsip, dan laporan. Project ini juga menyediakan API untuk aplikasi Android Flutter.

Repository ini berisi aplikasi web dan API Laravel. Aplikasi Android berada di repository terpisah: `BA_absensi`.

Sistem ini dibuat untuk membantu sekolah mencatat kehadiran secara lebih cepat, rapi, dan terpusat. Absensi dilakukan melalui QR Code, lalu data dapat dipantau oleh admin, guru piket, guru mapel, wali kelas, siswa, dan orang tua sesuai hak akses masing-masing.

## Daftar Isi

- [Teknologi](#teknologi)
- [Role Pengguna](#role-pengguna)
- [Fitur Utama](#fitur-utama)
- [Detail Modul](#detail-modul)
- [Struktur Project](#struktur-project)
- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Instalasi Lokal](#instalasi-lokal)
- [Konfigurasi ENV](#konfigurasi-env)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Akun dan Hak Akses](#akun-dan-hak-akses)
- [Alur Absensi QR](#alur-absensi-qr)
- [API Mobile](#api-mobile)
- [Database dan Migrasi](#database-dan-migrasi)
- [Perintah Penting](#perintah-penting)
- [Catatan Deploy](#catatan-deploy)
- [Troubleshooting](#troubleshooting)

## Teknologi

- Laravel 12
- PHP 8.2 atau lebih baru
- MySQL atau MariaDB
- Blade Template
- Vite
- Tailwind CSS
- Maatwebsite Excel
- Simple QR Code
- Firebase Cloud Messaging untuk notifikasi Android
- Geolocator pada aplikasi Android untuk validasi lokasi saat scan QR

## Role Pengguna

Sistem mendukung beberapa role dan konteks akses:

- `admin`: mengelola master data, absensi, rekap, kalender, notifikasi, arsip, backup, role akses, dan pengaturan sistem.
- `guru`: dapat menjadi guru mapel, guru piket, dan wali kelas sesuai data jadwal atau kelas.
- `piket`: akses khusus guru piket untuk absensi harian dan QR harian.
- `siswa`: scan QR, melihat dashboard, riwayat, kalender, nilai, kartu pelajar, dan mengajukan izin.
- `orang_tua`: memantau absensi dan pengajuan anak.
- `wali kelas`: konteks role guru yang menjadi wali kelas pada data kelas.

## Fitur Utama

### Admin

- Dashboard ringkasan data sekolah.
- Kelola data siswa, guru, admin, jurusan, kelas, mapel, jadwal pelajaran, guru piket, dan wali kelas.
- Kelola absensi harian siswa.
- Kelola absensi mata pelajaran.
- Rekap absensi harian dengan filter tanggal, bulan, tahun ajaran, semester, kelas, dan status.
- Rekap absensi mapel berdasarkan guru, kelas, mapel, jadwal, dan tanggal.
- Rekap guru piket dan jadwal guru piket.
- Rekap jadwal guru mapel.
- Rekap jadwal yang digantikan oleh guru pengganti.
- Pengaturan tahun ajaran dan semester aktif.
- Kalender sekolah untuk hari libur, tanggal merah, dan agenda sekolah.
- Auto alfa harian dengan pengecualian tanggal libur.
- Pengajuan izin atau sakit siswa.
- Notifikasi admin, notifikasi role, dan pengaturan notifikasi.
- Status login user web dan Android.
- Audit log untuk riwayat perubahan data.
- Role akses, delegasi role, dan pesan internal.
- Backup database, restore database, dan download backup.
- Arsip data terhapus, restore arsip, dan hapus permanen.
- Validasi tutup bulan agar laporan bulanan bisa dikunci.
- Pemeriksaan keamanan dan kesehatan data.
- Export laporan ke Excel dan PDF.

### Guru Piket

- Dashboard khusus guru piket.
- Tampilan tim piket per hari, bukan satu kartu per guru.
- Dukungan guru pengganti jika guru piket utama izin atau sakit.
- QR absensi harian masuk.
- QR absensi harian pulang.
- QR tim piket, sehingga satu QR dapat mewakili tim piket pada hari tersebut.
- Tampilan QR besar satu halaman untuk memudahkan scan.
- Absensi harian siswa dengan status masuk dan pulang.
- Filter absensi berdasarkan hadir, telat, izin, sakit, alfa, dan belum masuk.
- Pengajuan izin siswa yang bisa disetujui atau ditolak oleh piket.
- Riwayat absensi harian dengan filter status, kondisi absensi, periode, dan pencarian.
- Rekap jadwal piket.
- Finalisasi absensi harian.
- Status kehadiran guru piket.
- Integrasi ke Android secara native untuk dashboard piket, absensi, pengajuan izin, rekap jadwal, dan riwayat.

### Guru Mapel

- Dashboard khusus guru mapel.
- Jadwal mengajar berdasarkan hari, kelas, mapel, dan jam.
- QR absensi mapel per sesi pembelajaran.
- Tampilan QR mapel besar satu halaman.
- Verifikasi absensi mapel siswa.
- Edit dan lihat detail absensi mapel.
- Rekap absensi mapel.
- Riwayat absensi siswa pada sesi guru.
- Guru pengganti dapat menangani sesi ketika guru utama tidak hadir.
- Data absensi tetap dapat dikaitkan dengan guru utama walaupun sesi dijalankan guru pengganti.
- Finalisasi absensi mapel.
- Laporan bulanan guru mapel.

### Wali Kelas

- Dashboard khusus wali kelas.
- Data siswa sesuai kelas yang diampu.
- Informasi detail siswa termasuk orang tua dan nomor telepon orang tua.
- Absensi siswa kelas wali.
- Filter absensi berdasarkan tanggal, bulan, tahun, tahun ajaran, semester, dan status.
- Siswa rawan berdasarkan telat, alfa, izin, atau sakit.
- Catatan pembinaan siswa oleh wali kelas.
- Surat dan laporan siswa.
- Laporan bulanan wali kelas.
- Export laporan siswa dan absensi ke PDF.
- Integrasi ke Android secara native untuk dashboard wali kelas, data siswa, absensi, dan siswa rawan.

### Siswa dan Orang Tua

- Login Android untuk siswa.
- Login Android untuk orang tua.
- Dashboard siswa berisi status absensi hari ini.
- Dashboard orang tua untuk memantau data anak.
- Scan QR absensi harian masuk.
- Scan QR absensi harian pulang.
- Scan QR absensi mapel.
- Riwayat absensi dengan filter lengkap.
- Kalender siswa dan informasi hari libur.
- Pengajuan izin atau sakit dari siswa.
- Kartu pelajar digital.
- Notifikasi absensi untuk siswa dan orang tua.
- Peringatan ketika hari libur sehingga absensi tidak dihitung alfa.
- Jika siswa mencoba scan saat libur, aplikasi dapat memberi peringatan dan kembali ke dashboard.
- Validasi lokasi scan memakai Geolocator agar absensi hanya bisa dilakukan di area sekolah.

## Detail Modul

### 1. Master Data

Master data digunakan sebagai fondasi seluruh fitur absensi.

- Data siswa:
  - Nama siswa.
  - NIS.
  - Username.
  - Kelas.
  - Jurusan.
  - Nama orang tua.
  - Nomor telepon orang tua.
  - Detail profil siswa.
  - Import data siswa.
- Data guru:
  - Nama guru.
  - Username.
  - Role guru.
  - Penugasan guru mapel.
  - Penugasan guru piket.
  - Penugasan wali kelas.
- Data admin:
  - Admin biasa.
  - Superadmin.
  - Pengaturan level admin.
- Data kelas:
  - Nama kelas.
  - Jurusan.
  - Wali kelas.
  - Jumlah siswa.
- Data jurusan:
  - Nama jurusan.
  - Kode jurusan.
- Data mapel:
  - Nama mata pelajaran.
  - Kode mapel jika digunakan.
- Data jadwal:
  - Hari.
  - Jam mulai.
  - Jam selesai.
  - Kelas.
  - Mapel.
  - Guru utama.
  - Guru pengganti.
  - Status guru.

### 2. Absensi Harian

Absensi harian dipakai untuk mencatat kehadiran siswa pada hari sekolah.

- Siswa scan QR masuk.
- Siswa scan QR pulang.
- Sistem mencatat jam masuk dan jam pulang.
- Status masuk dapat berupa hadir, telat, izin, sakit, alfa, atau alpa.
- Status pulang dapat berupa pulang, pulang cepat, izin, sakit, alfa, atau alpa.
- Admin dan guru piket dapat melihat, membuat, mengubah, dan menghapus data absensi.
- Admin dapat melakukan sinkron rekap.
- Rekap dapat difilter berdasarkan tanggal, bulan, kelas, status, tahun ajaran, dan semester.
- Data hari libur tidak dihitung sebagai alfa.

### 3. Absensi Mata Pelajaran

Absensi mapel digunakan untuk mencatat kehadiran siswa pada setiap sesi pelajaran.

- Guru mapel membuat QR mapel berdasarkan jadwal.
- Siswa scan QR mapel.
- Sistem mencatat jam scan, status, kelas, mapel, dan jadwal.
- Guru mapel dapat melihat dan memverifikasi absensi siswa.
- Admin dapat mengelola absensi mapel.
- Sistem mendukung guru pengganti.
- Rekap mapel dapat digunakan untuk melihat kehadiran per guru, per kelas, per mapel, dan per tanggal.

### 4. QR Code

QR Code menjadi media utama pencatatan absensi.

- QR harian masuk.
- QR harian pulang.
- QR mapel.
- QR tim guru piket.
- QR memiliki token.
- QR memiliki masa berlaku.
- QR dapat ditampilkan besar satu halaman.
- QR divalidasi saat scan agar tidak sembarang token diterima.
- QR dapat ditolak jika sudah kadaluarsa atau digunakan di luar kondisi yang sesuai.
- Scan QR di Android dapat mengirim latitude, longitude, akurasi lokasi, dan jarak dari area sekolah.
- Aplikasi Android memakai Geolocator untuk memastikan siswa berada di area sekolah sebelum absensi diproses.

### 5. Guru Piket

Modul guru piket dipakai untuk mengelola absensi harian siswa.

- Tim piket ditampilkan per hari.
- Satu tim dapat berisi beberapa guru.
- Guru pengganti dapat ditampilkan jika guru utama berhalangan.
- Data absensi yang dibuat melalui QR tim tetap masuk ke konteks piket.
- Guru piket dapat memantau siswa hadir, telat, izin, sakit, alfa, dan belum masuk.
- Guru piket dapat memproses pengajuan izin.
- Guru piket dapat melihat riwayat absensi.
- Guru piket dapat melihat rekap jadwal.
- Guru piket dapat melakukan finalisasi harian.

### 6. Guru Mapel

Modul guru mapel dipakai untuk absensi per pelajaran.

- Guru melihat jadwal mengajar.
- Guru membuat QR mapel.
- Guru melihat QR aktif.
- Guru melihat QR dalam ukuran besar.
- Guru memantau siswa yang sudah scan.
- Guru dapat melihat detail absensi mapel.
- Guru pengganti dapat menjalankan sesi.
- Guru utama tetap dapat menerima data sesi yang digantikan sesuai kebutuhan rekap.

### 7. Wali Kelas

Modul wali kelas dipakai untuk memantau kondisi kelas.

- Wali kelas melihat daftar siswa di kelasnya.
- Wali kelas melihat absensi harian siswa kelas.
- Wali kelas melihat siswa rawan berdasarkan data 30 hari terakhir.
- Wali kelas dapat memberi catatan pembinaan.
- Wali kelas dapat melihat detail siswa.
- Wali kelas dapat membuat surat atau laporan siswa.
- Wali kelas dapat membuat laporan bulanan.
- Data wali kelas juga tersedia untuk aplikasi Android.

### 8. Pengajuan Izin dan Sakit

Pengajuan izin dipakai saat siswa tidak dapat hadir.

- Siswa atau orang tua mengajukan izin.
- Pengajuan memuat jenis, tanggal mulai, tanggal selesai, dan alasan.
- Guru piket atau admin dapat memproses pengajuan.
- Status pengajuan dapat berupa menunggu, disetujui, atau ditolak.
- Data pengajuan dapat mempengaruhi status absensi.
- Android guru piket memiliki filter pengajuan berdasarkan status, jenis, periode, dan pencarian.

### 9. Kalender Sekolah dan Hari Libur

Kalender sekolah dipakai untuk mengatur hari aktif dan hari libur.

- Admin dapat membuat data kalender sekolah.
- Admin dapat mengatur tanggal merah atau hari libur.
- Sistem mendukung hari libur berulang.
- Hari libur dapat ditampilkan pada dashboard siswa.
- Hari libur tidak dihitung alfa.
- Scan absensi dapat dicegah ketika tanggal sedang libur.

### 10. Notifikasi

Sistem notifikasi digunakan untuk memberi informasi penting ke user.

- Notifikasi admin ketika siswa absen.
- Notifikasi siswa.
- Notifikasi orang tua.
- Notifikasi hari libur.
- Notifikasi siswa rawan untuk wali kelas.
- Pengaturan notifikasi berdasarkan kebutuhan role.
- Dukungan Firebase Cloud Messaging untuk Android.

### 11. Rekap, Export, dan Laporan

Sistem menyediakan berbagai rekap dan laporan.

- Rekap absensi harian.
- Rekap absensi mapel.
- Rekap guru piket.
- Rekap jadwal guru mapel.
- Rekap jadwal yang digantikan.
- Rekap wali kelas.
- Export Excel.
- Export PDF.
- Laporan bulanan guru.
- Laporan bulanan piket.
- Laporan bulanan wali kelas.
- Validasi tutup bulan.

### 12. Arsip, Audit, dan Keamanan

Modul ini membantu menjaga data tetap aman dan bisa ditelusuri.

- Soft delete pada beberapa data inti.
- Arsip data terhapus.
- Restore data arsip.
- Hapus permanen data arsip.
- Audit log perubahan data.
- Riwayat perubahan user.
- Status login user web dan Android.
- Pengaturan keamanan.
- Pemeriksaan kesehatan data.
- Backup dan restore database.

## Struktur Project

```text
app/
  Http/
    Controllers/
    Middleware/
  Models/
  Services/
  Support/
config/
database/
  migrations/
public/
  css/
  js/
resources/
  views/
    dashboard/
    layouts/
routes/
  api.php
  web.php
storage/
tests/
```

File penting:

- `routes/web.php`: route utama aplikasi web.
- `routes/api.php`: endpoint API untuk Android dan scan QR.
- `app/Http/Controllers/Api`: controller API login, QR, absensi, dan user.
- `app/Http/Middleware/CheckRole.php`: middleware role API.
- `resources/views/dashboard`: halaman dashboard semua role.
- `public/css/pages`: CSS halaman dashboard.
- `database/migrations`: struktur tabel aplikasi.

## Kebutuhan Sistem

Pastikan perangkat sudah memiliki:

- PHP 8.2+
- Composer
- Node.js dan npm
- MySQL atau MariaDB
- XAMPP jika menjalankan di Windows lokal
- Git

Untuk mode Android lokal:

- Laravel server harus bisa diakses dari HP pada jaringan yang sama.
- IP di aplikasi Android harus diarahkan ke server Laravel, contoh `http://192.168.1.6:8000/api`.

## Instalasi Lokal

Clone repository:

```bash
git clone https://github.com/robby-saputra/absensi-qr.git
cd absensi-qr
```

Install dependency PHP:

```bash
composer install
```

Install dependency frontend:

```bash
npm install
```

Salin file ENV:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Buat database di MySQL, contoh:

```sql
CREATE DATABASE absensi_qr;
```

Jalankan migrasi:

```bash
php artisan migrate
```

Jika tersedia seeder di project, jalankan:

```bash
php artisan db:seed
```

Build asset:

```bash
npm run build
```

## Konfigurasi ENV

Contoh konfigurasi dasar:

```env
APP_NAME="Absensi QR"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_qr
DB_USERNAME=root
DB_PASSWORD=
```

Jika memakai Firebase atau FCM, sesuaikan konfigurasi pada:

```env
FCM_SERVER_KEY=
FIREBASE_PROJECT_ID=
```

Nama variabel dapat menyesuaikan isi `config/services.php` dan implementasi yang digunakan di project.

## Menjalankan Aplikasi

Jalankan server Laravel:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Jalankan Vite saat development:

```bash
npm run dev
```

Buka aplikasi web:

```text
http://localhost:8000
```

Jika digunakan oleh Android melalui jaringan lokal, buka dari HP memakai IP komputer:

```text
http://192.168.1.6:8000
```

Sesuaikan IP dengan jaringan komputer yang menjalankan Laravel.

## Akun dan Hak Akses

Login web berada di:

```text
/login
```

Setelah login, sistem mengarahkan user sesuai role dan konteks akses:

- Admin ke dashboard admin.
- Guru ke dashboard guru, lalu dapat memilih Guru Mapel, Guru Piket, atau Wali Kelas jika aktif.
- Piket ke dashboard piket.
- Siswa ke dashboard siswa.
- Orang tua ke dashboard orang tua.

Hak akses dipengaruhi oleh:

- Kolom `role` pada tabel `users`.
- Data jadwal pelajaran untuk guru mapel.
- Data guru piket untuk guru piket.
- Data `wali_kelas_id` pada tabel `kelas` untuk wali kelas.
- Middleware web dan API.

## Alur Absensi QR

### Absensi Harian

1. Guru piket membuka dashboard piket.
2. Guru piket membuat QR masuk atau pulang.
3. Siswa scan QR melalui aplikasi Android.
4. Sistem memvalidasi token QR, waktu berlaku, status libur, dan data siswa.
5. Data masuk ke tabel absensi harian.
6. Admin, guru piket, wali kelas, siswa, dan orang tua dapat melihat rekap sesuai hak akses.

### Absensi Mapel

1. Guru mapel membuka dashboard guru.
2. Guru mapel memulai sesi atau membuat QR mapel.
3. Siswa scan QR mapel melalui aplikasi Android.
4. Sistem mencatat kehadiran mapel berdasarkan jadwal.
5. Guru mapel dan admin dapat melihat rekap absensi mapel.

### Hari Libur

Hari libur diambil dari kalender sekolah. Jika tanggal termasuk libur:

- Siswa tidak dihitung alfa.
- Scan absensi harian, mapel, atau pulang dapat diblokir sesuai logic aplikasi.
- Notifikasi libur dapat ditampilkan ke siswa dan orang tua.

## API Mobile

Base URL lokal:

```text
http://localhost:8000/api
```

Base URL Android pada jaringan lokal:

```text
http://IP-KOMPUTER:8000/api
```

Endpoint utama:

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/login` | Login Android |
| POST | `/fcm/register-parent` | Registrasi token FCM orang tua |
| POST | `/qr/generate` | Generate QR |
| GET | `/users` | Data user untuk role tertentu |
| POST | `/scan` | Scan absensi umum |
| POST | `/scan-absensi` | Scan absensi harian |
| POST | `/scan-mapel` | Scan absensi mapel |
| GET | `/riwayat/{siswa_id}` | Riwayat absensi siswa |
| GET | `/siswa/dashboard/{siswa_id}` | Dashboard siswa |
| GET | `/siswa/kalender/{siswa_id}` | Kalender siswa |
| POST | `/siswa/pengajuan-izin` | Pengajuan izin atau sakit siswa |
| GET | `/mobile/role-context/{user_id}` | Konteks role guru di Android |
| GET | `/mobile/piket-dashboard/{user_id}` | Dashboard guru piket Android |
| GET | `/mobile/piket-absensi/{user_id}` | Absensi harian piket Android |
| GET | `/mobile/piket-riwayat/{user_id}` | Riwayat absensi piket Android |
| GET | `/mobile/piket-jadwal/{user_id}` | Rekap jadwal piket Android |
| GET | `/mobile/piket-pengajuan/{user_id}` | Pengajuan izin piket Android |
| POST | `/mobile/piket-pengajuan/{id}/review` | Setujui atau tolak pengajuan izin |
| GET | `/mobile/wali-dashboard/{user_id}` | Dashboard wali kelas Android |

## Database dan Migrasi

Beberapa tabel utama:

- `users`
- `kelas`
- `jurusan`
- `mapels`
- `jadwal_pelajarans`
- `guru_pikets`
- `qr_codes`
- `absensis`
- `absensi_mapels`
- `tahun_ajarans`
- `kalender_sekolahs`
- `student_permit_requests`
- `notifications`
- `notification_settings`
- `audit_logs`
- `role_delegations`
- `role_messages`
- `monthly_validation_statuses`
- `parent_fcm_tokens`

Migrasi penting:

- QR code dan absensi.
- Kelas dan relasi siswa.
- Tahun ajaran.
- Audit log.
- Data orang tua siswa.
- Kalender sekolah dan hari libur.
- Soft delete pada tabel inti.
- Pengajuan izin siswa.
- Pengaturan notifikasi.
- Role dashboard dan delegasi.
- Status login user.
- Team context QR guru piket.

## Perintah Penting

Bersihkan cache Laravel:

```bash
php artisan optimize:clear
```

Jalankan migrasi:

```bash
php artisan migrate
```

Rollback migrasi terakhir:

```bash
php artisan migrate:rollback
```

Build asset production:

```bash
npm run build
```

Mode development frontend:

```bash
npm run dev
```

Cek syntax PHP:

```bash
php -l routes/api.php
php -l routes/web.php
```

## Catatan Deploy

Saat deploy ke hosting atau server:

1. Pastikan PHP versi 8.2 atau lebih baru.
2. Upload semua file project kecuali folder yang tidak diperlukan seperti `.git`, `node_modules`, dan cache lokal.
3. Jalankan `composer install --no-dev --optimize-autoloader`.
4. Atur file `.env` sesuai database production.
5. Jalankan `php artisan key:generate` jika belum ada `APP_KEY`.
6. Jalankan `php artisan migrate`.
7. Jalankan `npm run build` sebelum upload asset atau build langsung di server.
8. Jalankan `php artisan optimize:clear`.
9. Pastikan folder `storage` dan `bootstrap/cache` bisa ditulis oleh server.
10. Arahkan document root hosting ke folder `public`.

## Troubleshooting

### Perubahan route atau tampilan belum muncul

Jalankan:

```bash
php artisan optimize:clear
```

Jika browser masih menampilkan tampilan lama, bersihkan cache browser atau gunakan hard refresh.

### Android tidak bisa konek ke Laravel

Pastikan:

- HP dan komputer berada di jaringan yang sama.
- Laravel dijalankan dengan `--host=0.0.0.0`.
- Firewall Windows mengizinkan port `8000`.
- IP di Android sesuai dengan IP komputer.
- URL API memakai format `http://IP-KOMPUTER:8000/api`.
- Izin lokasi Android aktif jika scan QR membutuhkan validasi lokasi.
- GPS perangkat aktif karena aplikasi Android memakai Geolocator untuk membaca posisi siswa.

### QR tidak bisa discan

Cek:

- Token QR masih berlaku.
- Tanggal bukan hari libur.
- Server Laravel aktif.
- Jam perangkat sesuai.
- Siswa login dengan akun yang benar.
- GPS aktif dan siswa berada dalam radius lokasi sekolah jika validasi lokasi diaktifkan.

### Data absensi tidak masuk rekap

Cek:

- Tahun ajaran aktif.
- Tanggal filter di halaman rekap.
- Data siswa memiliki `kelas_id`.
- Data absensi memiliki `id_siswa`.
- Jalankan sinkron rekap jika fitur tersedia di admin.

### Export Excel atau PDF bermasalah

Cek:

- Dependency Composer sudah terinstall.
- Folder `storage` bisa ditulis.
- Data filter tidak kosong.
- Jalankan `composer dump-autoload` jika class baru belum terbaca.

## Repository Terkait

- Web dan API Laravel: `robby-saputra/absensi-qr`
- Aplikasi Android Flutter: `robby-saputra/BA_absensi`

## Status Project

Project masih aktif dikembangkan. Beberapa fitur terbaru yang sudah tersedia:

- Dashboard guru multi konteks.
- Guru piket native di Android.
- Wali kelas native di Android.
- QR tim piket.
- Filter lengkap untuk riwayat dan pengajuan izin piket Android.
- Notifikasi absensi untuk admin, siswa, dan orang tua.
- Kalender libur sekolah agar hari libur tidak dihitung alfa.

## Lisensi

Project ini dibuat untuk kebutuhan sistem absensi sekolah. Gunakan, ubah, dan kembangkan sesuai kebutuhan internal pemilik project.
