# Absensi QR

## Deskripsi

Absensi QR adalah aplikasi web Laravel dan API untuk membantu SMK Bhakti Anindya mengelola absensi siswa berbasis QR Code. Sistem ini dipakai bersama dengan aplikasi Android Flutter yang berada di repository terpisah `BA_absensi`.

Website Laravel digunakan oleh admin, guru piket, guru mata pelajaran, dan wali kelas. Aplikasi Android Flutter hanya digunakan oleh siswa dan orang tua.

## Fokus Sistem

Sistem ini berfokus pada absensi siswa, bukan sistem akademik lengkap. Fitur utama yang dibahas adalah absensi harian, absensi mata pelajaran, QR Code, validasi lokasi, pengajuan izin/sakit, riwayat absensi, rekap/laporan absensi, notifikasi orang tua, dan pengaturan absensi.

## Teknologi

- Laravel 12
- PHP 8.2+
- MySQL/MariaDB
- Blade
- QR Code
- Firebase Cloud Messaging
- API untuk Flutter Android

## Role Pengguna

### Website Laravel

- Admin
- Guru Piket
- Guru Mata Pelajaran
- Wali Kelas

### Aplikasi Android Flutter

- Siswa
- Orang Tua

## Fitur Utama Website

- Login sesuai role
- Data siswa
- Data guru
- Data orang tua
- Data kelas
- Data jurusan
- Data mata pelajaran
- Jadwal pelajaran
- Guru piket
- Wali kelas
- Absensi harian
- Absensi mata pelajaran
- Pengajuan izin/sakit
- Rekap/laporan absensi
- Cetak laporan PDF jika tersedia
- Pengaturan absensi
- Lonceng notifikasi admin sederhana
- Kunci otomatis perubahan absensi berdasarkan Jam Kunci Absensi

## Fitur Utama Android

### Siswa

- Login siswa
- Dashboard siswa
- Scan QR absensi harian
- Scan QR absensi mata pelajaran
- Mengirim latitude, longitude, dan akurasi lokasi ke backend
- Riwayat absensi
- Pengajuan izin/sakit
- Logout

### Orang Tua

- Login orang tua
- Dashboard monitoring anak
- Riwayat kehadiran anak
- Status izin/sakit anak
- Notifikasi Firebase Cloud Messaging
- Logout

## Alur Absensi QR

1. Admin atau guru menyiapkan data dan QR absensi.
2. Siswa scan QR melalui aplikasi Android.
3. Aplikasi Android mengambil lokasi perangkat siswa.
4. Aplikasi mengirim token QR, latitude, longitude, dan akurasi lokasi ke Laravel.
5. Laravel memvalidasi QR dan lokasi berdasarkan Pengaturan Absensi.
6. Jika QR dan lokasi valid, data absensi disimpan.
7. Orang tua menerima notifikasi jika diperlukan.
8. Setelah melewati Jam Kunci Absensi, data absensi harian dan absensi mata pelajaran terkunci otomatis untuk guru/piket.

Laravel/backend menjadi sumber validasi utama untuk scan QR dan validasi lokasi. Flutter hanya mengambil lokasi perangkat dan mengirimkannya ke API.

## Kunci Otomatis Absensi

Absensi harian dan absensi mata pelajaran dapat dikoreksi oleh petugas terkait sebelum Jam Kunci Absensi. Setelah melewati jam tersebut, data absensi otomatis terkunci untuk guru piket dan guru mata pelajaran. Jika ada koreksi setelah batas waktu tersebut, perubahan hanya dapat dilakukan oleh admin melalui panel admin.

Jam Kunci Absensi dapat diatur melalui menu Pengaturan Absensi. Nilai defaultnya adalah 14:00.

## Pengaturan Absensi

Admin dapat mengatur parameter absensi melalui menu Pengaturan Absensi:

- Tahun ajaran aktif
- Semester aktif
- Latitude sekolah
- Longitude sekolah
- Radius absensi
- Jam masuk
- Batas terlambat
- Jam pulang
- Jam kunci absensi
- Masa aktif QR

Titik koordinat sekolah dan radius absensi disimpan di Pengaturan Absensi. Radius absensi default adalah 200 meter. Tahun ajaran dan semester hanya menjadi parameter periode absensi, bukan fitur pendaftaran siswa baru.

## Lonceng Notifikasi Admin

Lonceng notifikasi admin digunakan sebagai pemberitahuan singkat untuk aktivitas penting, seperti pengajuan izin/sakit baru dan informasi absensi. Fitur ini bukan chat, inbox, pesan internal, atau broadcast.

## API Mobile

Base URL lokal:

```text
http://localhost:8000/api
```

Base URL Android pada jaringan lokal:

```text
http://IP-KOMPUTER:8000/api
```

Endpoint utama untuk scope siswa dan orang tua:

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/login` | Login Android siswa/orang tua |
| POST | `/scan-absensi` | Scan QR absensi harian |
| POST | `/scan-mapel` | Scan QR absensi mata pelajaran |
| GET | `/riwayat/{siswa_id}` | Riwayat absensi siswa |
| GET | `/siswa/dashboard/{siswa_id}` | Dashboard siswa |
| GET | `/siswa/kalender/{siswa_id}` | Kalender dan status hari siswa |
| POST | `/siswa/pengajuan-izin` | Pengajuan izin/sakit siswa |
| GET | `/mobile/wali-dashboard/{user_id}` | Dashboard monitoring anak untuk orang tua |
| POST | `/fcm/register-parent` | Registrasi token FCM orang tua |

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

Buat database MySQL/MariaDB, lalu jalankan migrasi:

```bash
php artisan migrate
```

Build asset:

```bash
npm run build
```

Jalankan server Laravel:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Konfigurasi Android

Aplikasi Android Flutter berada di repository:

```text
robby-saputra/BA_absensi
```

Saat pengujian perangkat fisik, pastikan HP dan komputer berada pada jaringan yang sama. Arahkan base URL aplikasi Android ke alamat Laravel, misalnya:

```text
http://192.168.1.6:8000/api
```

## Firebase Cloud Messaging

Firebase Cloud Messaging digunakan untuk notifikasi orang tua. Token perangkat orang tua diregistrasikan dari aplikasi Android ke backend Laravel agar notifikasi status kehadiran anak dapat dikirim.

## Perintah Penting

Bersihkan cache Laravel:

```bash
php artisan optimize:clear
```

Cek route:

```bash
php artisan route:list
```

Jalankan test:

```bash
php artisan test
```

## Troubleshooting

### Android Tidak Bisa Terhubung ke Laravel

- Pastikan HP dan komputer berada di jaringan yang sama.
- Jalankan Laravel dengan `--host=0.0.0.0`.
- Pastikan firewall mengizinkan port Laravel.
- Pastikan base URL Android memakai IP komputer.

### QR Tidak Bisa Diproses

- Pastikan QR masih berlaku.
- Pastikan siswa login dengan akun siswa.
- Pastikan GPS aktif dan izin lokasi diberikan.
- Pastikan siswa berada dalam radius sekolah.
- Keputusan valid atau tidaknya QR dan lokasi ditentukan oleh Laravel.

### Data Absensi Tidak Muncul

- Pastikan tahun ajaran aktif sudah diatur.
- Pastikan siswa memiliki kelas.
- Pastikan filter tanggal/periode laporan benar.
- Coba refresh halaman atau login ulang.

## Catatan Scope

Sistem ini tidak membahas fitur nilai, e-learning, pendaftaran siswa baru, pembayaran, chat, rapor, kartu pelajar digital, atau manajemen akademik lengkap karena penelitian hanya berfokus pada sistem absensi siswa berbasis QR Code, Flutter, Laravel, Firebase Cloud Messaging, dan validasi lokasi.

## Repository Terkait

- Web dan API Laravel: `robby-saputra/absensi-qr`
- Aplikasi Android Flutter: `robby-saputra/BA_absensi`

## Lisensi

Project ini dibuat untuk kebutuhan sistem absensi sekolah dan skripsi. Gunakan dan kembangkan sesuai kebutuhan internal pemilik project.
