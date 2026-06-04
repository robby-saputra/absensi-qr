<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AttendanceSettingService
{
    public static function jamMasuk(): string
    {
        return self::get('jam_masuk', '07:00:00');
    }

    public static function jamPulang(): string
    {
        return self::get('jam_pulang', '14:00:00');
    }

    public static function batasTelat(): string
    {
        return self::get('batas_telat', '07:15:00');
    }

    public static function masaAktifQr(): int
    {
        return max((int) self::get('masa_aktif_qr', '30'), 1);
    }

    public static function statusDefaultAlfa(): string
    {
        return self::get('status_default_alfa', 'alfa');
    }

    public static function namaSekolah(): string
    {
        return self::get('nama_sekolah', 'Absensi QR');
    }

    public static function logoSekolah(): string
    {
        return self::get('logo_sekolah', 'img/logo-ba.png');
    }

    public static function latitudeSekolah(): float
    {
        return (float) self::get('latitude_sekolah', '-6.172564');
    }

    public static function longitudeSekolah(): float
    {
        return (float) self::get('longitude_sekolah', '106.627565');
    }

    public static function radiusAbsensi(): int
    {
        return max((int) self::get('radius_absensi', '200'), 1);
    }

    public static function all(): array
    {
        return [
            'jam_masuk' => self::jamMasuk(),
            'batas_telat' => self::batasTelat(),
            'jam_pulang' => self::jamPulang(),
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
            if (! Schema::hasTable('attendance_settings')) {
                return $default;
            }

            return DB::table('attendance_settings')
                ->where('key', $key)
                ->value('value') ?? $default;
        } catch (Throwable) {
            return $default;
        }
    }
}
