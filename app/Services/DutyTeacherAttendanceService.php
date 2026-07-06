<?php

namespace App\Services;

use App\Models\GuruPiketStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DutyTeacherAttendanceService
{
    public const BELUM_KONFIRMASI = 'belum_konfirmasi';
    public const HADIR = 'hadir';
    public const IZIN = 'izin';
    public const SAKIT = 'sakit';
    public const DIGANTIKAN = 'digantikan';
    public const SELESAI = 'selesai';

    public function statusFor(int $guruPiketId, string $tanggal, ?int $guruId = null): ?GuruPiketStatus
    {
        return GuruPiketStatus::query()->where('guru_piket_id', $guruPiketId)
            ->when($guruId, fn ($query) => $query->where('guru_id', $guruId), fn ($query) => $query->where('peran', 'utama'))
            ->whereDate('tanggal', $tanggal)->first();
    }

    public function currentStatus(?GuruPiketStatus $dailyStatus): string
    {
        return $dailyStatus?->status ?: self::BELUM_KONFIRMASI;
    }

    public function hasConfirmed(?GuruPiketStatus $dailyStatus): bool
    {
        return in_array($this->currentStatus($dailyStatus), [self::HADIR, self::IZIN, self::SAKIT], true)
            && $dailyStatus?->waktu_konfirmasi !== null;
    }

    public function isReplacementActive(?GuruPiketStatus $primaryStatus): bool
    {
        return in_array($primaryStatus?->status, [self::IZIN, self::SAKIT], true);
    }

    public function labelFor(object $jadwal, string $tanggal): string
    {
        $status = property_exists($jadwal, 'status_harian')
            ? $jadwal->status_harian
            : $this->statusFor((int) $jadwal->id, $tanggal)?->status;
        if (! $status) {
            return 'Belum Konfirmasi';
        }

        if ($status === self::HADIR && $jadwal->jam_selesai && Carbon::parse($tanggal.' '.$jadwal->jam_selesai)->isPast()) {
            return 'Selesai';
        }

        return match ($status) {
            self::HADIR => 'Sedang Bertugas',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit',
            self::DIGANTIKAN => 'Digantikan',
            self::SELESAI => 'Selesai',
            default => 'Belum Konfirmasi',
        };
    }

    public function confirm(int $guruPiketId, string $tanggal, string $status, int $actorId, string $source = 'web', ?string $note = null, string $role = 'utama', ?int $replacingTeacherId = null): GuruPiketStatus
    {
        abort_unless(in_array($status, [self::HADIR, self::IZIN, self::SAKIT, self::DIGANTIKAN, self::SELESAI], true), 422, 'Status guru piket tidak valid.');

        return DB::transaction(function () use ($guruPiketId, $tanggal, $status, $actorId, $source, $note, $role, $replacingTeacherId) {
            $existing = GuruPiketStatus::query()->where('guru_piket_id', $guruPiketId)->where('guru_id', $actorId)->whereDate('tanggal', $tanggal)->lockForUpdate()->first();
            abort_if($existing && $existing->waktu_konfirmasi, 422, 'Status guru piket pada tanggal ini sudah dikonfirmasi.');

            return GuruPiketStatus::query()->updateOrCreate(
                ['guru_piket_id' => $guruPiketId, 'guru_id' => $actorId, 'tanggal' => $tanggal],
                ['status' => $status, 'peran' => $role, 'menggantikan_guru_id' => $replacingTeacherId, 'waktu_konfirmasi' => now(), 'dipilih_oleh' => $actorId, 'sumber' => $source, 'keterangan' => $note]
            );
        });
    }
}
