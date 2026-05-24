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

    public static function all(): array
    {
        return [
            'jam_masuk' => self::jamMasuk(),
            'jam_pulang' => self::jamPulang(),
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
