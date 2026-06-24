# Absensi QR - Website dan API Laravel

Absensi QR adalah sistem informasi absensi sekolah berbasis website Laravel, QR Code, lokasi GPS, dan aplikasi Android Flutter. Repository ini berisi website utama sekaligus backend API untuk aplikasi mobile siswa dan orang tua.

Program ini dibuat untuk membantu sekolah mengelola absensi harian, absensi mata pelajaran, jadwal pelajaran, status guru mengajar, guru pengganti, rekap kehadiran, pengajuan izin/sakit, notifikasi, dan laporan absensi secara terpusat.

## Repository

- Website dan API Laravel: `https://github.com/robby-saputra/absensi-qr`
- Aplikasi mobile Flutter: `https://github.com/robby-saputra/BA_absensi`
- Branch utama pengembangan/rilis: `final`

## Teknologi

- Laravel 12
- PHP 8.2+
- MySQL atau MariaDB
- Blade template
- Vite
- QR Code
- Firebase Cloud Messaging untuk notifikasi mobile
- Flutter Android sebagai aplikasi mobile terpisah

## Aktor Pengguna

Website Laravel digunakan oleh:

- Admin
- Guru piket
- Guru mata pelajaran
- Wali kelas
- Siswa untuk akses web terbatas jika diperlukan

Aplikasi Android digunakan oleh:

- Siswa
- Orang tua

## Tujuan Sistem

Sistem ini dirancang agar proses absensi sekolah lebih tertib dan mudah dipantau. Admin mengatur data master dan jadwal, guru membuat QR sesuai tugasnya, siswa melakukan scan dari aplikasi Android, dan orang tua dapat memantau kehadiran anak melalui dashboard mobile.

Backend Laravel menjadi sumber kebenaran utama. Validasi QR, waktu, jadwal, status guru, kelas, lokasi, radius, hari libur, dan duplikasi absensi tetap diproses di backend, bukan hanya di aplikasi mobile.

## Fitur Website

- Login sesuai role pengguna.
- Dashboard admin, guru, guru piket, wali kelas, dan siswa.
- Pengelolaan data siswa, guru, kelas, jurusan, wali kelas, guru piket, dan jadwal.
- Pengaturan absensi sekolah, seperti tahun ajaran, semester, koordinat sekolah, radius, jam masuk, batas terlambat, jam pulang, jam kunci absensi, dan masa aktif QR.
- Pengelolaan kalender sekolah untuk libur, ujian, dan kegiatan.
- QR absensi harian untuk masuk dan pulang.
- QR absensi mata pelajaran berdasarkan jadwal dan Jam Pelajaran (JP).
- Status mengajar guru utama per tanggal.
- Alur guru pengganti ketika guru utama sakit atau izin.
- Rantai guru pengganti lanjutan jika pengganti pertama tidak bisa bertugas.
- Rekap absensi harian dan absensi mata pelajaran.
- Pengajuan izin/sakit siswa.
- Verifikasi dan koreksi data absensi sesuai hak akses.
- Laporan dan cetak PDF jika tersedia.
- Notifikasi operasional untuk admin, guru, siswa, dan orang tua.
- Pusat bantuan sesuai role pengguna.
- Tampilan responsif untuk desktop, tablet, dan HP.

## Fitur API Mobile

API Laravel dipakai aplikasi Android untuk:

- Login siswa dan orang tua.
- Mengambil dashboard siswa.
- Mengambil dashboard orang tua.
- Mengambil jadwal pelajaran hari ini.
- Mengambil kalender sekolah.
- Mengirim scan QR absensi harian.
- Mengirim scan QR absensi mata pelajaran.
- Mengambil riwayat absensi.
- Mengirim pengajuan izin/sakit.
- Mendaftarkan token Firebase Cloud Messaging.

Endpoint utama:

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/api/login` | Login Android siswa/orang tua |
| GET | `/api/siswa/dashboard/{siswa_id}` | Dashboard siswa |
| GET | `/api/siswa/kalender/{siswa_id}` | Kalender siswa |
| GET | `/api/riwayat/{siswa_id}` | Riwayat absensi siswa |
| POST | `/api/scan-absensi` | Scan QR absensi harian |
| POST | `/api/scan-mapel` | Scan QR absensi mapel |
| POST | `/api/siswa/pengajuan-izin` | Pengajuan izin/sakit |
| POST | `/api/fcm/register-device` | Registrasi token FCM |

## Alur Absensi Harian

1. Admin mengatur lokasi sekolah, radius absensi, jam masuk, jam pulang, dan masa aktif QR.
2. Guru piket membuka dashboard dan membuat QR masuk atau pulang.
3. Siswa membuka aplikasi Android dan scan QR.
4. Aplikasi mengirim token QR dan koordinat GPS ke API.
5. Backend memvalidasi token QR, waktu aktif, role siswa, lokasi, radius, tanggal, dan duplikasi scan.
6. Jika valid, data absensi tersimpan.
7. Dashboard siswa, orang tua, wali kelas, dan admin menampilkan data terbaru.

## Alur Absensi Mata Pelajaran

1. Admin membuat jadwal pelajaran berdasarkan kelas, mata pelajaran, guru utama, hari, jam mulai, jam selesai, dan JP.
2. Guru mata pelajaran membuka status mengajar untuk jadwal hari tersebut.
3. Jika guru utama hadir, guru utama dapat memulai sesi mapel dan membuat QR.
4. Jika guru utama sakit atau izin, sistem memakai data guru pengganti aktif.
5. Guru pengganti yang aktif dapat memulai sesi mapel dan membuat QR.
6. Siswa scan QR mapel melalui aplikasi Android.
7. Backend memvalidasi kelas, jadwal, waktu, sesi aktif, guru aktif, QR, dan lokasi.
8. Riwayat absensi mapel tersimpan dan dapat direkap.

## Guru Aktif dan Guru Pengganti

Status guru mengajar disimpan per tanggal agar status sakit/izin hari ini tidak memengaruhi jadwal minggu berikutnya.

Konsep penting:

- Guru utama adalah guru asli pada jadwal pelajaran.
- Guru aktif adalah guru yang benar-benar bertugas pada tanggal tersebut.
- Jika guru utama hadir, guru aktif adalah guru utama.
- Jika guru utama sakit/izin dan ada pengganti aktif, guru aktif adalah guru pengganti.
- Jika pengganti pertama tidak bisa hadir, admin dapat mengatur pengganti lanjutan.
- Tampilan admin dan API mobile harus memakai resolver guru aktif yang sama agar data konsisten.

## Pengaturan Absensi

Admin dapat mengatur:

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

Jam kunci absensi digunakan untuk membatasi koreksi data oleh role non-admin. Setelah melewati jam kunci, koreksi hanya dapat dilakukan oleh admin.

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

## Konfigurasi Penting

Pastikan `.env` berisi konfigurasi database, URL aplikasi, dan layanan yang dibutuhkan.

Contoh bagian penting:

```env
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database
DB_USERNAME=root
DB_PASSWORD=
```

Untuk notifikasi mobile, konfigurasi Firebase/FCM harus disesuaikan dengan project Firebase yang digunakan.

## Perintah Pengembangan

Bersihkan cache:

```bash
php artisan optimize:clear
php artisan view:clear
```

Cek route:

```bash
php artisan route:list
```

Jalankan test:

```bash
php artisan test
```

Jalankan scheduler lokal:

```bash
php artisan schedule:work
```

## Hubungan Dengan Aplikasi Mobile

Aplikasi Android Flutter memakai API dari repository ini. Untuk production, aplikasi mobile diarahkan ke:

```text
https://baabsensi.my.id/api
```

Untuk pengembangan lokal, base URL mobile dapat diarahkan ke alamat server Laravel lokal sesuai jaringan yang digunakan.

## Catatan Keamanan

- File `.env` tidak boleh di-commit.
- Credential database, Firebase private key, password hosting, dan secret lain tidak boleh masuk repository.
- Validasi absensi harus tetap dilakukan di backend.
- QR harus memiliki masa aktif.
- Lokasi dan radius sekolah harus divalidasi di backend.
- Release APK mobile tidak disimpan sebagai commit repository.

## Panduan Tambahan

Panduan penggunaan lengkap tersedia di:

```text
docs/PANDUAN_APLIKASI.md
```

## Lisensi dan Kegunaan

Project ini dibuat untuk kebutuhan sistem absensi sekolah dan penyusunan skripsi. Sistem dapat dikembangkan lebih lanjut sesuai kebutuhan sekolah atau penelitian.
