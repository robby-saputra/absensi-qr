<?php

namespace App\Services;

use App\Models\GuruPiketStatus;
use Illuminate\Support\Facades\DB;

class ActiveDutyTeacherResolver
{
    public function resolve(object $user, ?string $date = null): ?object
    {
        // Jika tanggal tidak dikirim, sistem memakai tanggal hari ini zona Jakarta.
        $date ??= now('Asia/Jakarta')->toDateString();

        // Hari dipakai untuk mencari jadwal guru piket, karena jadwal piket berbasis hari.
        $day = strtolower(\Carbon\Carbon::parse($date, 'Asia/Jakarta')->locale('id')->translatedFormat('l'));

        // Query ini mencari jadwal piket yang berhubungan dengan user:
        // bisa sebagai guru utama piket atau sebagai guru pengganti pada tanggal tersebut.
        $schedules = DB::table('guru_pikets as gp')->join('users as primary', 'primary.id', '=', 'gp.guru_id')
            ->where('gp.hari', $day)->where('gp.aktif', 1)->whereNull('gp.deleted_at')
            ->where(function ($query) use ($user, $date) {
                $query->where('gp.guru_id', $user->id)->orWhereExists(function ($sub) use ($user, $date) {
                    $sub->selectRaw('1')->from('guru_piket_replacements as r')->whereColumn('r.guru_piket_id', 'gp.id')
                        ->where('r.guru_pengganti_id', $user->id)->whereDate('r.tanggal', $date)->whereNull('r.deleted_at');
                });
            })->select('gp.*', 'primary.nama as nama_guru_utama')->get();

        foreach ($schedules as $schedule) {
            // Mengambil pengganti terbaru dari rantai pengganti guru piket.
            // Yang terbaru adalah pengganti yang sedang menunggu konfirmasi atau aktif.
            $latest = DB::table('guru_piket_replacements as r')->join('users as replacement', 'replacement.id', '=', 'r.guru_pengganti_id')
                ->where('r.guru_piket_id', $schedule->id)->whereDate('r.tanggal', $date)->whereNull('r.deleted_at')
                ->orderByDesc('r.urutan_penggantian')->select('r.*', 'replacement.nama as nama_pengganti')->first();

            // User dianggap valid jika dia guru utama piket atau pengganti terakhir yang masih relevan.
            $isPrimary = (int) $schedule->guru_id === (int) $user->id;
            $isLatestReplacement = $latest && (int) $latest->guru_pengganti_id === (int) $user->id
                && in_array($latest->status_penugasan, ['menunggu_konfirmasi', 'aktif'], true);
            if (! $isPrimary && ! $isLatestReplacement) continue;

            // Status harian menyimpan apakah guru piket sudah hadir, izin, sakit, atau belum konfirmasi.
            $daily = GuruPiketStatus::query()->where('guru_piket_id', $schedule->id)->where('guru_id', $user->id)
                ->whereDate('tanggal', $date)->first();
            $previousName = null;

            // Jika ini pengganti lanjutan, sistem mengambil nama pengganti sebelumnya untuk ditampilkan.
            if ($isLatestReplacement && $latest->menggantikan_replacement_id) {
                $previousName = DB::table('guru_piket_replacements as r')->join('users as u', 'u.id', '=', 'r.guru_pengganti_id')
                    ->where('r.id', $latest->menggantikan_replacement_id)->value('u.nama');
            }

            // Permission menentukan apakah user boleh melihat absensi, mengubah absensi, dan membuat QR.
            $permissions = app(DutyTeacherAssignmentService::class)->permissions((int) $schedule->id, (int) $user->id, $date);
            $attendance = app(DutyTeacherAttendanceService::class);
            $effectiveStatus = $attendance->effectiveStatus($daily, $date);

            // Object ini dikembalikan ke controller/dashboard agar tampilan tahu tugas user hari ini.
            return (object) [
                'schedule' => $schedule, 'assignment' => $latest, 'date' => $date,
                'role' => $isPrimary ? 'utama' : (($latest->urutan_penggantian ?? 1) === 1 ? 'pengganti_pertama' : 'pengganti_lanjutan'),
                'status' => $effectiveStatus, 'raw_status' => $daily?->status ?: 'belum_konfirmasi', 'daily_status' => $daily,
                'status_label' => $attendance->statusLabel($effectiveStatus, $daily?->sumber),
                'primary_name' => $schedule->nama_guru_utama, 'previous_replacement_name' => $previousName,
                'can_view_attendance' => $permissions['can_view_attendance'],
                'can_manage_attendance' => $permissions['can_manage_attendance'], 'can_manage_qr' => $permissions['can_manage_qr'],
            ];
        }
        return null;
    }

    public function hasActiveAssignment(object $user, ?string $date = null): bool
    {
        // Fungsi ringkas untuk mengecek apakah user punya tugas piket aktif.
        return $this->resolve($user, $date) !== null;
    }
}
