# Panduan Sistem Absensi QR

## 1. Gambaran Sistem

Website Laravel menjadi pusat data dan API. Android dipakai siswa serta orang tua. Semua absensi mengikuti tanggal, tahun ajaran, semester, kelas, jadwal, dan sesi **Jam Pelajaran (JP)**.

## 2. Admin

### Persiapan data

1. Isi jurusan dan kelas.
2. Tambahkan siswa, guru, dan wali kelas.
3. Aktifkan tahun ajaran dan semester.
4. Atur kalender libur, ujian, dan kegiatan.
5. Buat jadwal mapel lengkap dengan hari, JP mulai, jumlah JP, kelas, mapel, guru utama, dan guru pengganti.
6. Buat jadwal guru piket beserta calon penggantinya.
7. Atur radius sekolah, batas telat, masa aktif QR, jam kunci, dan batas konfirmasi guru.

Gunakan filter pada halaman master dan rekap untuk mencari tanggal, kelas, jurusan, mapel, JP, peran guru, status, semester, atau tahun ajaran.

### Pengganti guru piket lanjutan

Jika guru utama izin/sakit, pengganti pertama menerima tugas. Jika pengganti tersebut juga berhalangan, admin menunjuk pengganti berikutnya. Hanya pengganti terakhir yang aktif dan berstatus hadir yang dapat membuat QR serta mengelola absensi. Penugasan lama menjadi read-only.

### Rekap dan koreksi

Rekap harian menggabungkan data guru piket dan absensi mapel. Siswa yang belum scan tetap ditampilkan sebagai belum absen/alfa sesuai aturan. Setelah jam kunci, hanya admin yang dapat mengoreksi data. Gunakan tombol lihat, ubah, hapus, ekspor, dan PDF sesuai hak akses.

## 3. Guru Piket

1. Buka dashboard dan konfirmasi Hadir, Izin, atau Sakit.
2. Jika hadir dan menjadi petugas aktif, buat QR **Masuk** atau **Pulang**.
3. Pantau siswa yang sudah dan belum absen.
4. Koreksi data sebelum jam kunci.

QR menyimpan tanggal, jadwal piket, pembuat, petugas aktif, replacement, masa berlaku, dan status aktif. Saat tugas berpindah, QR lama tidak dapat digunakan.

## 4. Guru Mata Pelajaran

1. Buka jadwal hari ini dan periksa sesi JP.
2. Guru utama mengonfirmasi kondisi mengajar.
3. Jika guru utama berhalangan, guru pengganti mengonfirmasi tugas.
4. Petugas aktif memulai sesi dan menampilkan QR mapel.
5. Verifikasi absensi, riwayat, izin, dan rekap berdasarkan kelas serta sesi JP.

Status hari ini tidak dibawa ke minggu berikutnya. Akses detail siswa mengikuti guru utama atau pengganti yang benar-benar bertugas pada tanggal tersebut.

## 5. Wali Kelas

Wali kelas memantau siswa kelasnya, absensi harian/mapel, pengajuan izin, keterlambatan, dan daftar siswa yang memerlukan perhatian. Siswa yang terlambat tiga kali berturut-turut dalam tiga hari pada mapel terkait dapat masuk daftar perhatian.

## 6. Siswa dan Orang Tua

Siswa menggunakan Android untuk scan QR harian/mapel, melihat jadwal JP, riwayat, kalender, dan mengajukan izin. Orang tua memantau data anak dan menerima notifikasi. Kalender Android hanya memuat libur, ujian, dan kegiatan pada bulan yang dipilih.

## 7. Notifikasi FCM

- Informasi absensi masuk, mapel, dan pulang.
- Pengingat ketika mapel sedang berlangsung.
- Pengingat **Waktunya Absen Pulang** pukul 14.00.

Scheduler Laravel wajib aktif agar notifikasi terjadwal berjalan:

```bash
php artisan schedule:work
```

## 8. Operasional Server

Setelah deployment:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Pastikan worker scheduler aktif dan service account Firebase tersedia sesuai `config/services.php`/environment server.

## 9. Troubleshooting

- **Token tidak ditemukan:** login ulang Android dan periksa header Authorization.
- **QR tidak valid:** pastikan QR terbaru, aktif, belum kedaluwarsa, dan dibuat petugas aktif.
- **Scan ditolak:** cek GPS, radius, kelas, jadwal JP, tanggal, dan hari libur.
- **FCM tidak masuk:** cek izin notifikasi, token perangkat, service account, jaringan server, dan scheduler.
- **Data rekap berbeda:** periksa filter tanggal, tahun ajaran, kelas, mapel, dan JP.
- **Tampilan lama:** jalankan `php artisan optimize:clear` dan refresh tanpa cache.

## 10. Audit dan Pengujian

```bash
php artisan attendance:audit
php artisan attendance:repair --dry-run
php artisan test
php artisan route:list
```

Lakukan backup database sebelum migrasi atau perbaikan produksi.
