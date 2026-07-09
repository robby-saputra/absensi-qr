# Absensi QR

Absensi QR adalah sistem informasi absensi sekolah berbasis Laravel, QR Code, GPS/geofencing, REST API, dan aplikasi Android Flutter. Repository ini berisi aplikasi web utama sekaligus backend API untuk absensi harian, absensi mata pelajaran, status guru, guru pengganti, pengajuan izin/sakit, notifikasi, rekap, dan laporan.

## Tujuan Sistem

Sistem ini dibuat untuk membantu sekolah mengelola kehadiran siswa secara terpusat. Admin mengatur data master dan jadwal, guru piket mengelola QR masuk/pulang, guru mata pelajaran mengelola QR per jadwal JP, siswa melakukan scan melalui Android, dan orang tua memantau kehadiran anak melalui aplikasi mobile.

Backend Laravel menjadi sumber kebenaran untuk validasi QR, waktu aktif, lokasi, radius, jadwal, status guru aktif, data siswa, dan duplikasi scan.

## Repository

- Website dan API Laravel: `https://github.com/robby-saputra/absensi-qr`
- Aplikasi mobile Flutter: `https://github.com/robby-saputra/BA_absensi`
- Branch rilis yang digunakan: `final`

## Teknologi

Teknologi yang digunakan berdasarkan konfigurasi project:

- PHP `^8.2`
- Laravel Framework `^12.0`
- MySQL atau MariaDB
- Blade template
- Vite `^6.0.11`
- Tailwind CSS `^4.0.0`
- QR Code melalui `simplesoftwareio/simple-qrcode`
- Laravel Excel `maatwebsite/excel`
- REST API untuk aplikasi mobile
- Firebase Cloud Messaging untuk notifikasi mobile
- Flutter Android pada repository mobile terpisah
- cPanel/LiteSpeed pada deployment hosting

## Arsitektur Web, API, dan Mobile

- Web Laravel digunakan admin, guru, guru piket, wali kelas, dan siswa web bila aksesnya tersedia.
- API Laravel digunakan aplikasi Android siswa dan orang tua.
- Aplikasi Android mengirim token QR, data lokasi, dan data pengajuan ke backend.
- Backend memvalidasi semua aturan penting sebelum menyimpan absensi.
- Notifikasi operasional dikirim melalui notifikasi web dan Firebase Cloud Messaging.

## Role Pengguna

- **Admin:** mengelola master data, jadwal, pengaturan, monitoring, pengajuan, laporan, arsip, dan koreksi.
- **Guru Piket:** mengelola QR masuk/pulang dan absensi harian sesuai jadwal piket aktif.
- **Guru Mata Pelajaran:** mengelola status mengajar, sesi mapel, QR mapel, dan verifikasi absensi mapel.
- **Guru Pengganti:** melakukan konfirmasi sendiri sebelum menjadi petugas/guru aktif.
- **Wali Kelas:** memantau siswa pada kelas wali dan laporan kehadiran.
- **Siswa:** melakukan scan QR, melihat riwayat, dan mengajukan izin/sakit.
- **Orang Tua:** memantau kehadiran anak melalui aplikasi Android.

## Fitur Utama

- Login sesuai role.
- Dashboard admin, guru, guru piket, wali kelas, dan siswa.
- Data siswa, guru, kelas, jurusan, wali kelas, guru piket, jadwal mapel, dan kalender sekolah.
- Pengaturan tahun ajaran, semester, jam masuk, batas terlambat, jam pulang, jam kunci absensi, lokasi sekolah, radius, dan masa aktif QR.
- QR absensi masuk, QR absensi mata pelajaran, dan QR pulang.
- Validasi lokasi GPS/geofencing.
- Status guru utama dan guru pengganti per tanggal.
- Monitoring Verifikasi Guru oleh admin.
- Batalkan Verifikasi dengan status Menunggu Verifikasi Ulang dan bypass cutoff terbatas.
- Pengajuan izin/sakit siswa.
- Rekap absensi harian dan absensi mata pelajaran.
- Laporan, print, Excel, dan PDF bila tersedia pada halaman terkait.
- Arsip data.
- Firebase Cloud Messaging untuk notifikasi mobile.
- Pusat Bantuan sesuai role.

## Alur Absensi Harian

1. Admin mengatur lokasi sekolah, radius, jam masuk, jam pulang, jam kunci, dan masa aktif QR.
2. Guru piket aktif membuat QR masuk atau pulang.
3. Siswa scan QR melalui aplikasi Android.
4. Aplikasi mengirim token QR dan koordinat GPS ke API.
5. Backend memvalidasi token, tanggal, waktu aktif, role siswa, lokasi, radius, dan duplikasi.
6. Data absensi tersimpan dan tampil pada dashboard, rekap, serta aplikasi orang tua.

## Alur Absensi Mata Pelajaran

1. Admin membuat jadwal berdasarkan kelas, mapel, guru utama, hari, jam, dan JP.
2. Guru utama memilih status mengajar sebelum cutoff.
3. Guru aktif memulai sesi mapel dan membuat QR.
4. Siswa scan QR mapel melalui aplikasi Android.
5. Backend memvalidasi jadwal, kelas, sesi aktif, guru aktif, QR, lokasi, dan duplikasi.
6. Data absensi mapel tersimpan dan dapat direkap.

## Alur Status Guru Utama

### Guru Piket

- Cutoff normal guru piket utama adalah pukul 07.00 WIB.
- Sebelum cutoff, guru memilih Hadir, Izin, atau Sakit.
- Jika tidak memilih sampai cutoff, status efektif dapat menjadi **Hadir Otomatis**.
- Hadir Manual dan Hadir Otomatis dibedakan pada tampilan dan audit.

### Guru Mata Pelajaran

- Cutoff normal guru mapel utama adalah pukul 06.30 WIB atau setting cutoff yang berlaku.
- Sebelum cutoff, guru memilih Hadir, Izin, atau Sakit.
- Jika tidak memilih sampai cutoff, status efektif dapat menjadi **Hadir Otomatis**.
- Guru dengan Hadir Otomatis tidak dihitung sebagai guru belum verifikasi.

## Alur Guru Pengganti

Guru pengganti adalah pihak berbeda dari guru utama dan wajib melakukan verifikasi sendiri.

- Setelah guru utama memilih Izin atau Sakit, pengganti pertama berstatus **Menunggu Konfirmasi**.
- Guru pengganti tidak pernah menjadi Hadir Otomatis.
- Pengganti memilih Hadir, Izin, atau Sakit.
- Jika memilih Hadir, pengganti menjadi petugas/guru aktif.
- Jika memilih Izin atau Sakit, admin dapat menunjuk pengganti lanjutan.
- Pengganti lanjutan juga wajib melakukan konfirmasi sendiri.
- Konfirmasi lama pengganti tidak dipakai ulang pada penugasan baru.

## Monitoring Verifikasi Guru

Admin dapat membuka halaman Monitoring Verifikasi Guru untuk:

- memilih tanggal tugas;
- melihat tab Guru Piket dan Guru Mata Pelajaran;
- melihat status efektif dan sumber status manual/otomatis;
- melihat guru cadangan;
- melihat pengganti aktif dan pengganti lanjutan;
- melihat petugas aktif atau guru aktif;
- membuka detail;
- membatalkan verifikasi guru utama atau pengganti bila statusnya dapat dikoreksi.

Halaman ini memakai service status efektif agar konsisten dengan dashboard guru, jadwal admin, QR, dan dashboard admin.

## Batalkan Verifikasi dan Bypass Cutoff

Admin dapat membatalkan verifikasi guru utama atau guru pengganti melalui Monitoring Verifikasi Guru.

Alur umum:

1. Admin memilih **Batalkan Verifikasi**.
2. Admin wajib mengisi alasan.
3. Status berubah menjadi **Menunggu Verifikasi Ulang**.
4. Guru memperoleh bypass cutoff khusus.
5. Form verifikasi tampil kembali walaupun batas waktu sudah lewat.
6. Guru memilih Hadir, Izin, atau Sakit.
7. Setelah status baru tersimpan, bypass berakhir.
8. Form kembali terkunci.
9. Perubahan dicatat dalam audit log.

Bypass tidak menghapus cutoff secara global. Bypass hanya berlaku untuk satu guru, satu tanggal, satu jadwal atau tugas, dan satu kali verifikasi ulang.

Pembatalan verifikasi tidak menghapus absensi siswa, histori scan, atau histori QR. Sistem hanya memperbarui status guru, status pengganti, hak akses QR, dan audit log.

## Hak Akses QR

- QR masuk dan pulang hanya untuk guru piket aktif atau operator piket yang berwenang.
- QR mata pelajaran hanya untuk guru mapel aktif.
- Guru pengganti belum mendapat akses QR sebelum memilih Hadir.
- Pengganti lama tidak dapat memakai akses QR setelah penugasan dibatalkan atau digantikan.
- Hanya satu petugas/guru aktif dalam satu tugas.
- Pengganti terakhir yang memilih Hadir menjadi petugas aktif.

## Instalasi Lokal

```bash
git clone https://github.com/robby-saputra/absensi-qr.git
cd absensi-qr
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Jalankan server lokal:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Jika ingin diakses dari HP pada jaringan lokal:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Konfigurasi `.env`

Pastikan konfigurasi dasar tersedia:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_qr
DB_USERNAME=root
DB_PASSWORD=
```

Konfigurasi Firebase/FCM disesuaikan dengan project Firebase sekolah. Jangan commit file `.env`, password, token, private key, atau kredensial hosting.

## Migrasi Database

Jalankan migration lokal:

```bash
php artisan migrate
```

Cek status migration:

```bash
php artisan migrate:status
```

Seeder dapat dijalankan jika tersedia dan memang dibutuhkan:

```bash
php artisan db:seed
```

## Menjalankan Scheduler

Untuk pengembangan lokal:

```bash
php artisan schedule:work
```

Pada production, scheduler Laravel sebaiknya dipanggil oleh cron hosting sesuai konfigurasi server.

## Menjalankan Test

Jalankan test:

```bash
php artisan test
```

Jalankan subset test verifikasi guru:

```bash
php artisan test --filter=TeacherVerification
```

## Database Testing Terpisah

Jangan menjalankan test menggunakan database kerja utama.

Gunakan database khusus, misalnya `absensi_qr_testing`, melalui `.env.testing`:

```env
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_qr_testing
DB_USERNAME=root
DB_PASSWORD=
```

Jika konfigurasi testing menunjuk ke database utama, test yang memakai transaksi, migration, atau refresh data dapat merusak data kerja. Jangan commit `.env.testing` yang berisi kredensial asli.

## Deployment cPanel

Struktur hosting yang digunakan:

- Repository Laravel: `/home/baabsen1/absensi-qr`
- Document root: `/home/baabsen1/public_html`

Isi folder `public` Laravel disajikan melalui `public_html`. Jika ada perubahan asset CSS/JS di `public`, asset perlu disalin atau disinkronkan ke `public_html` sesuai struktur hosting saat ini.

Alur update aman:

```bash
cd ~/absensi-qr
git pull --ff-only origin final
/opt/cpanel/ea-php82/root/usr/bin/php "$(command -v composer)" install --no-dev --optimize-autoloader
/opt/cpanel/ea-php82/root/usr/bin/php artisan migrate:status
/opt/cpanel/ea-php82/root/usr/bin/php artisan migrate --force
/opt/cpanel/ea-php82/root/usr/bin/php artisan optimize:clear
```

Jangan menulis password database hosting di dokumentasi, commit, issue, atau chat publik.

## Struktur Folder Penting

- `app/Http/Controllers` - controller web, dashboard, admin, dan API.
- `app/Services` - service status guru, QR, notifikasi, dan logika domain.
- `app/Actions` - aksi transaksi seperti reset verifikasi.
- `resources/views` - Blade dashboard dan Pusat Bantuan.
- `routes/web.php` - route web.
- `routes/api.php` - route API mobile.
- `database/migrations` - struktur database.
- `tests` - test feature dan unit.
- `public` - asset publik yang disajikan web server.

## Keamanan

- Jangan commit `.env`, password, token, private key, dan kredensial hosting.
- Validasi QR, lokasi, radius, waktu, role, dan status guru dilakukan di backend.
- QR memiliki masa aktif dan hanya boleh digunakan pada konteks yang valid.
- Admin harus mengisi alasan saat membatalkan verifikasi.
- Audit log digunakan untuk perubahan penting.
- Hindari perintah destruktif pada database kerja seperti `migrate:fresh`, `migrate:refresh`, atau `db:wipe`.

## Troubleshooting

- **Status masih Belum Konfirmasi:** guru belum memilih status atau tanggal belum melewati cutoff.
- **Status Hadir Otomatis:** guru utama belum memilih sampai cutoff; status ini tidak berlaku untuk guru pengganti.
- **Form verifikasi tidak muncul:** status sudah dipilih, jadwal tidak sesuai tanggal, atau akun bukan guru yang terkait.
- **Form muncul setelah reset admin:** itu bypass khusus satu kali untuk verifikasi ulang.
- **QR dinonaktifkan:** belum ada petugas/guru aktif, pengganti belum memilih Hadir, jadwal belum mulai, jadwal sudah selesai, atau QR lama dicabut.
- **Status tidak sinkron:** buka Monitoring Verifikasi Guru dan jalankan `php artisan optimize:clear`.
- **Error 403:** akun tidak punya role atau tugas yang sesuai.
- **Error 500:** periksa log server dan cache Laravel tanpa membagikan kredensial.
- **CSS hosting belum berubah:** pastikan asset public sudah tersalin ke `public_html` dan cache browser dibersihkan.
- **Migration pending:** cek `php artisan migrate:status`.

## Status Pengembangan

Branch `final` digunakan sebagai acuan rilis. Fitur utama absensi, QR, status guru, guru pengganti, monitoring verifikasi, bypass cutoff, rekap, notifikasi, dan Pusat Bantuan tersedia dan terus disempurnakan sesuai kebutuhan sekolah.

## Lisensi dan Keterangan Akademik

Project ini dibuat untuk kebutuhan sistem absensi sekolah dan penyusunan skripsi. Penggunaan, pengembangan, dan deployment perlu menyesuaikan kebijakan sekolah serta keamanan data pengguna.
