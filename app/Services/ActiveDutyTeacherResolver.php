<?php

namespace App\Services;

use App\Models\GuruPiketStatus;
use Illuminate\Support\Facades\DB;

class ActiveDutyTeacherResolver
{
    public function resolve(object $user, ?string $date = null): ?object
    {
        $date ??= now('Asia/Jakarta')->toDateString();
        $day = strtolower(\Carbon\Carbon::parse($date, 'Asia/Jakarta')->locale('id')->translatedFormat('l'));
        $schedules = DB::table('guru_pikets as gp')->join('users as primary', 'primary.id', '=', 'gp.guru_id')
            ->where('gp.hari', $day)->where('gp.aktif', 1)->whereNull('gp.deleted_at')
            ->where(function ($query) use ($user, $date) {
                $query->where('gp.guru_id', $user->id)->orWhereExists(function ($sub) use ($user, $date) {
                    $sub->selectRaw('1')->from('guru_piket_replacements as r')->whereColumn('r.guru_piket_id', 'gp.id')
                        ->where('r.guru_pengganti_id', $user->id)->whereDate('r.tanggal', $date)->whereNull('r.deleted_at');
                });
            })->select('gp.*', 'primary.nama as nama_guru_utama')->get();

        foreach ($schedules as $schedule) {
            $latest = DB::table('guru_piket_replacements as r')->join('users as replacement', 'replacement.id', '=', 'r.guru_pengganti_id')
                ->where('r.guru_piket_id', $schedule->id)->whereDate('r.tanggal', $date)->whereNull('r.deleted_at')
                ->orderByDesc('r.urutan_penggantian')->select('r.*', 'replacement.nama as nama_pengganti')->first();
            $isPrimary = (int) $schedule->guru_id === (int) $user->id;
            $isLatestReplacement = $latest && (int) $latest->guru_pengganti_id === (int) $user->id
                && in_array($latest->status_penugasan, ['menunggu_konfirmasi', 'aktif'], true);
            if (! $isPrimary && ! $isLatestReplacement) continue;

            $daily = GuruPiketStatus::query()->where('guru_piket_id', $schedule->id)->where('guru_id', $user->id)
                ->whereDate('tanggal', $date)->first();
            $previousName = null;
            if ($isLatestReplacement && $latest->menggantikan_replacement_id) {
                $previousName = DB::table('guru_piket_replacements as r')->join('users as u', 'u.id', '=', 'r.guru_pengganti_id')
                    ->where('r.id', $latest->menggantikan_replacement_id)->value('u.nama');
            }
            $permissions = app(DutyTeacherAssignmentService::class)->permissions((int) $schedule->id, (int) $user->id, $date);
            return (object) [
                'schedule' => $schedule, 'assignment' => $latest, 'date' => $date,
                'role' => $isPrimary ? 'utama' : (($latest->urutan_penggantian ?? 1) === 1 ? 'pengganti_pertama' : 'pengganti_lanjutan'),
                'status' => $daily?->status ?: 'belum_konfirmasi', 'daily_status' => $daily,
                'primary_name' => $schedule->nama_guru_utama, 'previous_replacement_name' => $previousName,
                'can_view_attendance' => $permissions['can_view_attendance'],
                'can_manage_attendance' => $permissions['can_manage_attendance'], 'can_manage_qr' => $permissions['can_manage_qr'],
            ];
        }
        return null;
    }

    public function hasActiveAssignment(object $user, ?string $date = null): bool
    {
        return $this->resolve($user, $date) !== null;
    }
}
