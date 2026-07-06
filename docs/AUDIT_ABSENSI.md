# Audit Sistem Absensi

## Temuan utama

- Jadwal piket mingguan sebelumnya juga menyimpan status kehadiran sehingga status Senin lama dapat terbaca pada Senin berikutnya.
- Beberapa endpoint API mempercayai `user_id` dari request dan belum membatasi akses berdasarkan token.
- Scan belum seluruhnya memakai transaksi/locking dan sejumlah pemeriksaan belum mengecualikan arsip.
- QR mapel belum ditolak tegas berdasarkan tanggal dan kelas siswa.
- Toleransi keterlambatan mapel belum mempunyai satu sumber konfigurasi.
- Rekap dan PDF guru piket membaca status mingguan; PDF mapel hanya memuat record yang sudah absen.
- Restore arsip belum memeriksa konflik data aktif dan perubahan manual belum mempunyai audit log absensi yang bertahan.

## Perbaikan

- Status harian guru piket dipindahkan ke `guru_piket_statuses`, unik untuk jadwal dan tanggal, serta mendukung soft-delete tanpa menghalangi record aktif baru.
- API memakai token hash dan identitas dari middleware. Parameter ID lama tetap diterima untuk kompatibilitas tetapi harus cocok dengan token.
- Scan harian dan mapel menggunakan transaksi, row lock, tanggal hari ini, status aktif, dan query nonarsip.
- QR mapel memvalidasi tanggal, masa berlaku, status aktif, kelas, hari, serta tahun ajaran aktif.
- `TeachingPeriodService` menjadi sumber aturan batas JP dan toleransi terlambat.
- Rekap/PDF guru piket memakai status harian. Rekap/PDF mapel mencakup siswa yang belum scan serta filter tanggal, kelas, mapel, JP, status, pencarian, dan tahun ajaran.
- Auto-alfa menolak tanggal masa depan dan eksekusi sebelum batas, serta bersifat idempotent.
- Arsip, restore, force delete, dan CRUD manual mapel dicatat pada `attendance_audit_logs`; restore absensi memeriksa konflik aktif.

## Status standar

Status baru disimpan dalam huruf kecil: `belum_konfirmasi`, `hadir`, `terlambat`, `izin`, `sakit`, `alfa`, `digantikan`, dan `selesai`. Label antarmuka boleh diterjemahkan tanpa mengubah nilai database.

## Operasional

- Audit aman: `php artisan attendance:audit`
- Simulasi perbaikan: `php artisan attendance:repair --dry-run`
- Migration produksi dijalankan dengan `php artisan migrate` setelah backup database.
- Migration tidak menghapus absensi lama. Status piket lama hanya dimigrasikan ketika `status_dipilih_at` menyediakan tanggal yang dapat dipercaya.
