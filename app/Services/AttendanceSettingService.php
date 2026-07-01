<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AttendanceSettingService
{
    public static function jamMasuk(): string
    {
        // Jam masuk default dipakai jika admin belum mengisi pengaturan di database.
        return self::get('jam_masuk', '07:00:00');
    }

    public static function jamPulang(): string
    {
        // Jam pulang menjadi pembanding untuk menentukan status pulang atau pulang_cepat.
        return self::get('jam_pulang', '14:00:00');
    }

    public static function jamKunciAbsensi(): string
    {
        // Jam kunci membatasi koreksi absensi oleh role non-admin.
        return self::normalizeTime(self::get('jam_kunci_absensi', '14:00:00'));
    }

    public static function batasTelat(): string
    {
        // Batas telat dipakai saat siswa scan QR masuk.
        return self::get('batas_telat', '07:15:00');
    }

    public static function masaAktifQr(): int
    {
        // Masa aktif QR minimal 1 menit agar QR tidak bernilai nol atau negatif.
        return max((int) self::get('masa_aktif_qr', '30'), 1);
    }

    public static function statusDefaultAlfa(): string
    {
        // Status ini dipakai ketika sistem menandai siswa yang tidak absen.
        return self::get('status_default_alfa', 'alfa');
    }

    public static function namaSekolah(): string
    {
        // Nama sekolah ditampilkan pada halaman dan laporan.
        return self::get('nama_sekolah', 'Absensi QR');
    }

    public static function logoSekolah(): string
    {
        // Logo sekolah dipakai untuk tampilan dan dokumen laporan.
        return self::get('logo_sekolah', 'img/logo-ba.png');
    }

    public static function latitudeSekolah(): float
    {
        // Latitude sekolah menjadi titik pusat validasi lokasi GPS siswa.
        return (float) self::get('latitude_sekolah', '-6.172564');
    }

    public static function longitudeSekolah(): float
    {
        // Longitude sekolah dipasangkan dengan latitude untuk menghitung jarak siswa.
        return (float) self::get('longitude_sekolah', '106.627565');
    }

    public static function radiusAbsensi(): int
    {
        // Radius absensi menentukan jarak maksimal siswa dari titik sekolah.
        return max((int) self::get('radius_absensi', '200'), 1);
    }

    public static function all(): array
    {
        // Mengumpulkan semua pengaturan agar controller/view bisa mengambilnya sekaligus.
        return [
            'jam_masuk' => self::jamMasuk(),
            'batas_telat' => self::batasTelat(),
            'jam_pulang' => self::jamPulang(),
            'jam_kunci_absensi' => self::jamKunciAbsensi(),
            'masa_aktif_qr' => self::masaAktifQr(),
            'status_default_alfa' => self::statusDefaultAlfa(),
            'nama_sekolah' => self::namaSekolah(),
            'logo_sekolah' => self::logoSekolah(),
            'latitude_sekolah' => self::latitudeSekolah(),
            'longitude_sekolah' => self::longitudeSekolah(),
            'radius_absensi' => self::radiusAbsensi(),
        ];
    }

    public static function setMany(array $values): void
    {
        // Menyimpan banyak pengaturan sekaligus dari form admin.
        foreach ($values as $key => $value) {
            DB::table('attendance_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private static function get(string $key, string $default): string
    {
        try {
            // Jika tabel belum ada, sistem memakai nilai default agar aplikasi tetap berjalan.
            if (! Schema::hasTable('attendance_settings')) {
                return $default;
            }

            // Mengambil nilai setting berdasarkan key; jika kosong, kembali ke default.
            return DB::table('attendance_settings')
                ->where('key', $key)
                ->value('value') ?? $default;
        } catch (Throwable) {
            // Jika terjadi error database, default dipakai agar proses absensi tidak langsung mati.
            return $default;
        }
    }

    private static function normalizeTime(string $value): string
    {
        // Jika format hanya jam dan menit, tambahkan detik agar konsisten menjadi H:i:s.
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value.':00';
        }

        // Jika format sudah lengkap, nilai dikembalikan apa adanya.
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        // Jika format tidak dikenali, gunakan jam kunci default.
        return '14:00:00';
    }
}
