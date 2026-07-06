# Absensi QR

Absensi QR adalah sistem absensi sekolah berbasis Laravel, QR Code, dan aplikasi Android Flutter. Website Laravel dipakai sebagai pusat pengelolaan data, absensi, QR, laporan, dan bantuan penggunaan. Aplikasi Android dipakai siswa dan orang tua untuk scan QR, melihat riwayat, dan menerima notifikasi.

## Status Versi Saat Ini

- Website sudah responsif untuk HP, tablet, dan desktop.
- Tabel dashboard otomatis berubah menjadi card list pada layar HP.
- Sidebar role sudah mobile-friendly.
- Dashboard wali kelas dan guru piket sudah memakai desain card modern.
- Jadwal guru piket admin sudah dikelompokkan per hari dan jam tugas.
- Pusat Bantuan tersedia untuk admin, guru, guru piket, wali kelas, siswa, dan publik.
- Jadwal dan laporan telah memakai sesi Jam Pelajaran (JP).
- Guru piket mendukung guru utama, pengganti pertama, dan rantai pengganti lanjutan per tanggal.
- QR harian terikat pada petugas piket aktif dan otomatis dinonaktifkan ketika tugas dialihkan.
- Android siswa dan orang tua menerima FCM mapel aktif serta pengingat absen pulang pukul 14.00.

## Teknologi

- Laravel 12
- PHP 8.2+
- MySQL/MariaDB
- Blade
- QR Code
- Firebase Cloud Messaging
- Flutter Android sebagai aplikasi mobile terpisah

## Role Pengguna

Website Laravel:

- Admin
- Guru Piket
- Guru Mata Pelajaran
- Wali Kelas
- Siswa web untuk riwayat dan pengajuan sederhana

Aplikasi Android Flutter:

- Siswa
- Orang Tua

## Fitur Website

- Login sesuai role.
- Dashboard responsif dan mobile-friendly.
- Data siswa, guru, kelas, jurusan, wali kelas, dan guru piket.
- Jadwal pelajaran dan kalender sekolah.
- Guru piket dikelompokkan per hari dan jam tugas.
- QR absensi harian oleh guru piket.
- QR absensi mata pelajaran oleh guru mapel.
- Status mengajar harian guru utama dan guru pengganti.
- Konfirmasi guru pengganti saat guru utama izin/sakit.
- Absensi harian dan absensi mapel.
- Pengajuan izin/sakit.
- Rekap dan laporan absensi.
- Cetak PDF jika tersedia.
- Pengaturan absensi, lokasi sekolah, radius, jam masuk, batas telat, jam pulang, jam kunci, dan masa aktif QR.
- Pusat Bantuan per role.
- Notifikasi admin dan orang tua.
- Notifikasi admin saat guru pengganti tidak bisa hadir.
- Kunci otomatis absensi setelah Jam Kunci Absensi.

## Alur Penggunaan Website

1. Admin mengisi data master: siswa, guru, kelas, jurusan, wali kelas, guru piket, jadwal, dan kalender sekolah.
2. Admin membuka Pengaturan Absensi untuk mengatur tahun ajaran aktif, semester, koordinat sekolah, radius, jam masuk, batas telat, jam pulang, jam kunci, dan masa aktif QR.
3. Guru piket membuka dashboard piket, mengonfirmasi status tugas jika diperlukan, lalu membuat QR masuk/pulang harian.
4. Guru mata pelajaran membuka menu Status Mengajar untuk memilih status harian: Hadir, Izin, atau Sakit.
5. Jika guru utama hadir, guru utama memulai sesi mapel dan menampilkan QR absensi mapel.
6. Jika guru utama izin/sakit, guru pengganti membuka Status Mengajar lalu memilih Saya Bertugas atau Tidak Bisa Hadir.
7. Jika guru pengganti memilih Saya Bertugas, guru pengganti dapat memulai sesi mapel dan menampilkan QR.
8. Jika guru pengganti memilih Tidak Bisa Hadir, sistem mengirim notifikasi admin agar admin segera mengatur pengganti lanjutan.
9. Siswa scan QR melalui aplikasi Android.
10. Backend Laravel memvalidasi token QR, waktu aktif, lokasi, radius, dan data siswa.
11. Wali kelas memantau ringkasan kehadiran dan siswa rawan telat/alfa.
12. Admin melihat rekap, laporan, pengajuan izin/sakit, notifikasi operasional, dan melakukan koreksi jika data sudah terkunci.

## Alur Guru Utama dan Guru Pengganti

Status mengajar guru mapel disimpan per tanggal, bukan permanen di master jadwal. Dengan begitu status sakit/izin hari ini tidak terbawa ke minggu berikutnya.

Alur status:

1. Guru utama memilih status jadwal hari ini sebelum batas konfirmasi guru yang dikonfigurasi (default pukul 07.00).
2. Jika guru utama memilih Hadir, hanya guru utama yang bisa memulai sesi mapel.
3. Jika guru utama memilih Izin/Sakit, guru pengganti yang terdaftar akan melihat jadwal tersebut di menu Status Mengajar.
4. Guru pengganti wajib mengonfirmasi Hadir, Izin, atau Sakit. Pengganti yang hadir menjadi petugas operasional; pengganti berhalangan diteruskan admin ke pengganti lanjutan.
5. Hanya pengganti terakhir dalam rantai yang aktif dan hadir yang memperoleh akses operasional.
5. QR mapel, edit/verifikasi absensi mapel, dan tampilan siswa membaca status harian tersebut.

Catatan:

- Jika guru utama belum memilih Izin/Sakit, guru pengganti hanya menunggu status guru utama.
- Jika guru pengganti belum konfirmasi Saya Bertugas, tombol mulai sesi tetap nonaktif.
- Jika guru pengganti memilih Tidak Bisa Hadir, jadwal menunggu penanganan admin.

## Alur Absensi QR

1. QR dibuat oleh guru piket atau guru mata pelajaran.
2. Siswa scan QR lewat aplikasi Android.
3. Android mengirim token QR, latitude, longitude, dan akurasi lokasi ke API Laravel.
4. Laravel memvalidasi QR dan lokasi berdasarkan Pengaturan Absensi.
5. Jika valid, absensi tersimpan.
6. Orang tua menerima notifikasi jika fitur FCM aktif.
7. Setelah Jam Kunci Absensi, koreksi oleh guru/piket terkunci dan hanya admin yang dapat mengubah data.

## Pusat Bantuan

Pusat Bantuan dapat dibuka dari:

- Login publik: `/bantuan`
- Admin: `/dashboard/bantuan?context=admin`
- Guru: `/dashboard/bantuan?context=guru`
- Guru Piket: `/dashboard/bantuan?context=piket`
- Wali Kelas: `/dashboard/bantuan?context=wali`
- Siswa Web: `/dashboard/bantuan?context=siswa`

Isi bantuan disesuaikan dengan role pengguna, termasuk panduan dashboard, scan QR, pengajuan izin/sakit, Jam Kunci Absensi, tampilan mobile, dan kendala umum.

## Kunci Otomatis Absensi

Absensi harian dan absensi mata pelajaran dapat dikoreksi oleh guru/piket sebelum Jam Kunci Absensi. Setelah melewati jam tersebut, data otomatis terkunci untuk role non-admin. Koreksi setelah batas waktu hanya dilakukan oleh admin melalui panel admin.

Nilai default Jam Kunci Absensi adalah `14:00`, dan dapat diubah dari menu Pengaturan Absensi.

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

Radius default adalah 200 meter. Laravel/backend menjadi sumber validasi utama untuk QR dan lokasi.

## API Mobile

Base URL lokal:

```text
http://localhost:8000/api
```

Base URL Android perangkat fisik:

```text
http://IP-KOMPUTER:8000/api
```

Base URL emulator Android Studio:

```text
http://10.0.2.2:8000/api
```

Endpoint utama:

| Method | Endpoint | Keterangan |
| --- | --- | --- |
| POST | `/login` | Login Android siswa/orang tua |
| POST | `/scan-absensi` | Scan QR absensi harian |
| POST | `/scan-mapel` | Scan QR absensi mata pelajaran |
| GET | `/riwayat/{siswa_id}` | Riwayat absensi siswa |
| GET | `/siswa/dashboard/{siswa_id}` | Dashboard siswa Android |
| GET | `/siswa/kalender/{siswa_id}` | Kalender dan status hari siswa |
| POST | `/siswa/pengajuan-izin` | Pengajuan izin/sakit siswa |
| POST | `/fcm/register-device` | Registrasi token FCM siswa/orang tua |

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

Jalankan server lokal untuk akses dari komputer:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Jalankan server lokal untuk akses dari HP/jaringan lokal:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Konfigurasi Android

Pastikan HP dan komputer berada pada jaringan yang sama. Arahkan base URL Android ke IP komputer, misalnya:

```text
http://192.168.1.11:8000/api
```

Jika memakai emulator Android Studio, gunakan:

```text
http://10.0.2.2:8000/api
```

## Perintah Penting

Bersihkan cache Laravel:

```bash
php artisan optimize:clear
```

Bersihkan cache view:

```bash
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

Jalankan scheduler untuk finalisasi status, sinkronisasi rekap, dan FCM terjadwal:

```bash
php artisan schedule:work
```

## Panduan Penggunaan

Panduan per role, alur JP, guru pengganti, QR, laporan, Android, dan troubleshooting tersedia di [docs/PANDUAN_APLIKASI.md](docs/PANDUAN_APLIKASI.md).

## Troubleshooting

Android tidak bisa konek ke Laravel:

- Pastikan server Laravel berjalan.
- Untuk HP fisik, jalankan Laravel dengan `--host=0.0.0.0`.
- Pastikan HP dan komputer berada di jaringan yang sama.
- Pastikan firewall mengizinkan port `8000`.
- Gunakan `10.0.2.2` jika memakai emulator Android Studio.

QR tidak bisa diproses:

- Pastikan QR masih berlaku.
- Pastikan siswa login dengan akun siswa.
- Pastikan GPS aktif dan izin lokasi diberikan.
- Pastikan siswa berada dalam radius sekolah.
- Pastikan jam QR belum kedaluwarsa.

Tampilan website di HP kurang rapi:

- Pastikan cache browser dibersihkan.
- Jalankan `php artisan view:clear`.
- Refresh halaman setelah CSS/JS terbaru dimuat.

Data absensi tidak muncul:

- Pastikan tahun ajaran aktif sudah diatur.
- Pastikan siswa memiliki kelas.
- Pastikan jadwal dan guru piket sudah dibuat.
- Cek filter tanggal/periode laporan.

## Repository Terkait

- Web dan API Laravel: `robby-saputra/absensi-qr`
- Aplikasi Android Flutter: `robby-saputra/BA_absensi`

## Lisensi

Project ini dibuat untuk kebutuhan sistem absensi sekolah dan skripsi. Gunakan dan kembangkan sesuai kebutuhan internal pemilik project.
