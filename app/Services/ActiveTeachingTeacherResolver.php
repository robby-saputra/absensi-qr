<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActiveTeachingTeacherResolver
{
    public function resolve(int $scheduleId, string $date): object
    {
        $schedule = DB::table('jadwal_pelajarans')->where('id', $scheduleId)->whereNull('deleted_at')->first();
        abort_if(! $schedule, 404);
        $daily = DB::table('jadwal_guru_statuses')->where('jadwal_id', $scheduleId)->whereDate('tanggal', $date)->first();
        $primaryStatus = $daily?->status_guru ?: 'normal';
        $chain = DB::table('jadwal_guru_replacements as r')->join('users as u', 'u.id', '=', 'r.guru_pengganti_id')
            ->where('r.jadwal_id', $scheduleId)->whereDate('r.tanggal', $date)->whereNull('r.deleted_at')
            ->select('r.*', 'u.nama as nama_pengganti')->orderBy('r.urutan_penggantian')->get();
        $active = $primaryStatus === 'normal' ? null : $chain->where('status_penugasan', 'aktif')->last();
        if (! $active && $primaryStatus !== 'normal' && $daily?->pengganti_status === 'bertugas' && $daily?->guru_pengganti_id) {
            $active = (object) ['guru_pengganti_id' => $daily->guru_pengganti_id, 'urutan_penggantian' => 1, 'status_penugasan' => 'aktif', 'nama_pengganti' => DB::table('users')->where('id', $daily->guru_pengganti_id)->value('nama')];
        }

        $decision = $this->decision($primaryStatus, (int) $schedule->guru_id, $chain, $active);

        return (object) [
            'schedule' => $schedule, 'daily_status' => $daily, 'primary_status' => $primaryStatus,
            'chain' => $chain, 'active_replacement' => $active,
            'active_teacher_id' => $decision->active_teacher_id,
            'needs_replacement' => $decision->needs_replacement,
        ];
    }

    public function decision(string $primaryStatus, int $primaryTeacherId, Collection $chain, ?object $active = null): object
    {
        $active ??= $primaryStatus === 'normal' ? null : $chain->where('status_penugasan', 'aktif')->last();
        $latest = $chain->last();
        return (object) [
            'active_teacher_id' => $primaryStatus === 'normal' ? $primaryTeacherId : ($active ? (int) $active->guru_pengganti_id : null),
            'needs_replacement' => $primaryStatus !== 'normal' && ! $active && (! $latest || $latest->status_penugasan === 'berhalangan'),
        ];
    }

    public function ensureFirst(object $schedule, string $date, int $actorId, string $reason): ?object
    {
        if (! $schedule->guru_pengganti_id) return null;
        return DB::transaction(function () use ($schedule, $date, $actorId, $reason) {
            DB::table('jadwal_guru_replacements')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->lockForUpdate()->get();
            DB::table('jadwal_guru_replacements')->updateOrInsert(
                ['jadwal_id' => $schedule->id, 'tanggal' => $date, 'guru_pengganti_id' => $schedule->guru_pengganti_id],
                ['guru_utama_id' => $schedule->guru_id, 'urutan_penggantian' => 1, 'status_penugasan' => 'menunggu_konfirmasi', 'alasan' => $reason, 'ditunjuk_oleh' => $actorId, 'updated_at' => now('Asia/Jakarta'), 'created_at' => now('Asia/Jakarta')]
            );
            return DB::table('jadwal_guru_replacements')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->orderByDesc('urutan_penggantian')->first();
        });
    }

    public function markStatus(int $scheduleId, int $teacherId, string $date, string $attendance): void
    {
        DB::transaction(function () use ($scheduleId, $teacherId, $date, $attendance) {
            $row = DB::table('jadwal_guru_replacements')->where('jadwal_id', $scheduleId)->where('guru_pengganti_id', $teacherId)
                ->whereDate('tanggal', $date)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $row) return;
            DB::table('jadwal_guru_replacements')->where('id', $row->id)->update([
                'status_kehadiran' => $attendance,
                'status_penugasan' => $attendance === 'hadir' ? 'aktif' : 'berhalangan',
                'mulai_aktif_at' => $attendance === 'hadir' ? now('Asia/Jakarta') : $row->mulai_aktif_at,
                'selesai_at' => $attendance === 'hadir' ? null : now('Asia/Jakarta'), 'updated_at' => now('Asia/Jakarta'),
            ]);
        });
    }

    public function candidates(object $schedule, string $date): Collection
    {
        $used = DB::table('jadwal_guru_replacements')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->whereNull('deleted_at')
            ->pluck('guru_pengganti_id')->push($schedule->guru_id)->push($schedule->guru_pengganti_id)->filter()->unique();
        return DB::table('users as u')->where('u.role', 'guru')->where('u.aktif', 1)->whereNull('u.deleted_at')->whereNotIn('u.id', $used)
            ->whereNotExists(function ($q) use ($date) {
                $q->selectRaw('1')->from('jadwal_guru_statuses as s')->whereDate('s.tanggal', $date)
                    ->where(function ($x) {
                        $x->where(fn ($p) => $p->whereColumn('s.guru_utama_id', 'u.id')->whereIn('s.status_guru', ['izin', 'sakit', 'inval']))
                            ->orWhere(fn ($p) => $p->whereColumn('s.guru_pengganti_id', 'u.id')->where('s.pengganti_status', 'tidak_hadir'));
                    });
            })
            ->whereNotExists(function ($q) use ($schedule, $date) {
                $q->selectRaw('1')->from('jadwal_guru_replacements as ar')->join('jadwal_pelajarans as aj', 'aj.id', '=', 'ar.jadwal_id')
                    ->whereColumn('ar.guru_pengganti_id', 'u.id')->whereDate('ar.tanggal', $date)->where('ar.status_penugasan', 'aktif')
                    ->where('aj.id', '!=', $schedule->id)->where('aj.jam_mulai', '<', $schedule->jam_selesai)->where('aj.jam_selesai', '>', $schedule->jam_mulai)
                    ->whereNull('ar.deleted_at')->whereNull('aj.deleted_at');
            })
            ->whereNotExists(function ($q) use ($schedule) {
                $q->selectRaw('1')->from('jadwal_pelajarans as j')->where('j.id', '!=', $schedule->id)->where('j.hari', $schedule->hari)
                    ->whereNull('j.deleted_at')->where(fn ($x) => $x->whereColumn('j.guru_id', 'u.id')->orWhereColumn('j.guru_pengganti_id', 'u.id'))
                    ->where('j.jam_mulai', '<', $schedule->jam_selesai)->where('j.jam_selesai', '>', $schedule->jam_mulai);
            })->whereNotExists(function ($q) use ($schedule) {
                $q->selectRaw('1')->from('guru_pikets as p')->whereColumn('p.guru_id', 'u.id')->where('p.hari', $schedule->hari)->where('p.aktif', 1)
                    ->whereNull('p.deleted_at')->where('p.jam_mulai', '<', $schedule->jam_selesai)->where('p.jam_selesai', '>', $schedule->jam_mulai);
            })->orderBy('u.nama')->select('u.id', 'u.nama')->get();
    }

    public function assignNext(object $schedule, string $date, int $teacherId, int $adminId, string $reason): object
    {
        abort_unless($this->candidates($schedule, $date)->contains('id', $teacherId), 422, 'Guru tidak tersedia atau memiliki jadwal bentrok.');
        return DB::transaction(function () use ($schedule, $date, $teacherId, $adminId, $reason) {
            $chain = DB::table('jadwal_guru_replacements')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->whereNull('deleted_at')->lockForUpdate()->orderBy('urutan_penggantian')->get();
            if ($chain->isEmpty()) {
                $legacy = DB::table('jadwal_guru_statuses')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->first();
                if ($legacy?->guru_pengganti_id) {
                    $legacyId = DB::table('jadwal_guru_replacements')->insertGetId([
                        'jadwal_id' => $schedule->id, 'tanggal' => $date, 'guru_utama_id' => $schedule->guru_id, 'guru_pengganti_id' => $legacy->guru_pengganti_id,
                        'urutan_penggantian' => 1, 'status_penugasan' => $legacy->pengganti_status === 'bertugas' ? 'aktif' : 'berhalangan',
                        'status_kehadiran' => $legacy->pengganti_status === 'bertugas' ? 'hadir' : 'sakit', 'alasan' => $legacy->pengganti_alasan,
                        'ditunjuk_oleh' => $adminId, 'mulai_aktif_at' => $legacy->pengganti_status === 'bertugas' ? $legacy->pengganti_dipilih_at : null,
                        'selesai_at' => $legacy->pengganti_status === 'tidak_hadir' ? $legacy->pengganti_dipilih_at : null,
                        'created_at' => now('Asia/Jakarta'), 'updated_at' => now('Asia/Jakarta'),
                    ]);
                    $chain = collect([DB::table('jadwal_guru_replacements')->where('id', $legacyId)->first()]);
                }
            }
            $previous = $chain->last();
            abort_if($previous && $previous->status_penugasan === 'aktif', 422, 'Masih ada guru pengganti yang aktif.');
            $order = ($previous?->urutan_penggantian ?? 0) + 1;
            $id = DB::table('jadwal_guru_replacements')->insertGetId([
                'jadwal_id' => $schedule->id, 'tanggal' => $date, 'guru_utama_id' => $schedule->guru_id, 'guru_pengganti_id' => $teacherId,
                'menggantikan_replacement_id' => $previous?->id, 'urutan_penggantian' => $order, 'status_penugasan' => 'menunggu_konfirmasi',
                'alasan' => $reason, 'ditunjuk_oleh' => $adminId, 'created_at' => now('Asia/Jakarta'), 'updated_at' => now('Asia/Jakarta'),
            ]);
            if ($previous) DB::table('jadwal_guru_replacements')->where('id', $previous->id)->update(['selesai_at' => now('Asia/Jakarta'), 'updated_at' => now('Asia/Jakarta')]);
            DB::table('jadwal_pelajarans')->where('id', $schedule->id)->update(['guru_pengganti_id' => $teacherId, 'updated_at' => now('Asia/Jakarta')]);
            DB::table('jadwal_guru_statuses')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->update([
                'guru_pengganti_id' => $teacherId, 'pengganti_status' => null, 'pengganti_alasan' => null, 'pengganti_dipilih_at' => null, 'updated_at' => now('Asia/Jakarta'),
            ]);
            app(AttendanceAuditService::class)->record('assign_teaching_replacement', 'jadwal_guru_replacements', $id, $previous, DB::table('jadwal_guru_replacements')->where('id', $id)->first(), request(), $reason);
            return DB::table('jadwal_guru_replacements')->where('id', $id)->first();
        });
    }
}
