<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActiveTeachingTeacherResolver
{
    public const BELUM_KONFIRMASI = 'belum_konfirmasi';

    public const HADIR_OTOMATIS = 'hadir_otomatis';

    public const MENUNGGU_VERIFIKASI_ULANG = 'menunggu_verifikasi_ulang';

    public function cutoff(): string
    {
        return (string) (DB::table('attendance_settings')->where('key', 'subject_teacher_attendance_cutoff')->value('value') ?: '06:30:00');
    }

    public function cutoffAt(string $date, ?CarbonInterface $at = null): CarbonInterface
    {
        return Carbon::parse($date, 'Asia/Jakarta')
            ->startOfDay()
            ->setTimeFromTimeString($this->cutoff());
    }

    public function isPastCutoff(string $date, ?CarbonInterface $at = null): bool
    {
        $at ??= now('Asia/Jakarta');
        $at = $at->copy()->timezone('Asia/Jakarta');

        return $at->gt($this->cutoffAt($date, $at));
    }

    public function resolve(int $scheduleId, string $date): object
    {
        // Mengambil satu jadwal pelajaran aktif berdasarkan ID.
        $schedule = DB::table('jadwal_pelajarans')->where('id', $scheduleId)->whereNull('deleted_at')->first();
        abort_if(! $schedule, 404);

        // Untuk konsistensi, satu jadwal tetap diproses lewat resolveMany.
        return $this->resolveMany(collect([$schedule]), $date)->get((int) $scheduleId);
    }

    public function resolveMany(Collection $schedules, string $date): Collection
    {
        // Jadwal diubah menjadi key by ID agar hasil resolver mudah dicocokkan kembali.
        $schedules = $schedules->keyBy(fn ($schedule) => (int) $schedule->id);
        if ($schedules->isEmpty()) {
            return collect();
        }

        $scheduleIds = $schedules->keys()->values();

        // Status harian guru menyimpan apakah guru utama hadir, izin, sakit, atau digantikan pada tanggal tertentu.
        $dailyStatuses = DB::table('jadwal_guru_statuses')
            ->whereIn('jadwal_id', $scheduleIds)
            ->whereDate('tanggal', $date)
            ->get()
            ->keyBy(fn ($daily) => (int) $daily->jadwal_id);

        // Chain replacement adalah rantai guru pengganti.
        // Ini dipakai ketika pengganti pertama juga berhalangan dan admin menunjuk pengganti lanjutan.
        $chains = DB::table('jadwal_guru_replacements as r')
            ->join('users as u', 'u.id', '=', 'r.guru_pengganti_id')
            ->whereIn('r.jadwal_id', $scheduleIds)
            ->whereDate('r.tanggal', $date)
            ->whereNull('r.deleted_at')
            ->whereIn('r.status_penugasan', ['aktif', 'berhalangan', 'menunggu_konfirmasi'])
            ->select('r.*', 'u.nama as nama_pengganti')
            ->orderBy('r.jadwal_id')
            ->orderBy('r.urutan_penggantian')
            ->get()
            ->groupBy(fn ($replacement) => (int) $replacement->jadwal_id);

        // Semua ID guru dikumpulkan agar nama guru bisa diambil sekali saja.
        $teacherIds = $schedules->pluck('guru_id')
            ->merge($dailyStatuses->pluck('guru_pengganti_id'))
            ->merge($chains->flatten(1)->pluck('guru_pengganti_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $teacherNames = DB::table('users')
            ->whereIn('id', $teacherIds)
            ->pluck('nama', 'id');

        return $schedules->mapWithKeys(function ($schedule) use ($date, $dailyStatuses, $chains, $teacherNames) {
            $daily = $dailyStatuses->get((int) $schedule->id);

            $status = $this->resolvePrimaryStatus($schedule, $daily, $date);
            $primaryStatus = $status->effective_status;
            $chain = $chains->get((int) $schedule->id, collect())->values();

            // Jika guru utama tidak normal, sistem mencari pengganti aktif terakhir.
            $active = $this->primaryIsPresent($primaryStatus) ? null : $chain->where('status_penugasan', 'aktif')->last();

            // Bagian ini menjaga kompatibilitas dengan data lama yang masih menyimpan pengganti di tabel status harian.
            if (! $active && ! in_array($primaryStatus, ['normal', self::HADIR_OTOMATIS, self::BELUM_KONFIRMASI], true) && $daily?->pengganti_status === 'bertugas' && $daily?->guru_pengganti_id) {
                $active = (object) [
                    'jadwal_id' => (int) $schedule->id,
                    'guru_pengganti_id' => (int) $daily->guru_pengganti_id,
                    'urutan_penggantian' => 1,
                    'status_penugasan' => 'aktif',
                    'status_kehadiran' => 'hadir',
                    'nama_pengganti' => $teacherNames[(int) $daily->guru_pengganti_id] ?? null,
                ];
            }

            // Decision menentukan guru aktif, pengganti aktif, dan apakah masih butuh pengganti baru.
            $decision = $this->decision($primaryStatus, (int) $schedule->guru_id, $chain, $active);
            $activeTeacherId = $decision->active_teacher_id ? (int) $decision->active_teacher_id : null;
            $activeReplacement = $decision->active_replacement;

            // Role guru aktif dipakai di tampilan dan disimpan ke absensi mapel.
            $role = $this->primaryIsPresent($primaryStatus)
                ? 'guru_utama'
                : ($activeReplacement ? ((int) ($activeReplacement->urutan_penggantian ?? 1) > 1 ? 'pengganti_lanjutan' : 'pengganti_pertama') : null);

            // Hasil akhir resolver berisi status guru utama, rantai pengganti, dan guru aktif hari itu.
            return [(int) $schedule->id => (object) [
                'schedule' => $schedule,
                'date' => $date,
                'daily_status' => $daily,
                'raw_status' => $status->raw_status,
                'effective_status' => $status->effective_status,
                'status_label' => $status->status_label,
                'status_source' => $status->status_source,
                'is_manual' => $status->is_manual,
                'is_automatic_cutoff' => $status->is_automatic_cutoff,
                'requires_admin_attention' => $status->requires_admin_attention,
                'status_reason' => $status->reason,
                'primary_status' => $primaryStatus,
                'primary_status_label' => $status->status_label,
                'chain' => $chain,
                'active_replacement' => $activeReplacement,
                'latest_replacement' => $chain->last(),
                'active_teacher_id' => $activeTeacherId,
                'active_teacher_name' => $activeTeacherId ? ($teacherNames[$activeTeacherId] ?? null) : null,
                'active_teacher_role' => $role,
                'active_teacher_role_label' => $this->roleLabel($role),
                'needs_replacement' => $decision->needs_replacement,
            ]];
        });
    }

    public function decision(string $primaryStatus, int $primaryTeacherId, Collection $chain, ?object $active = null): object
    {
        // Jika guru utama normal, guru aktif adalah guru utama.
        // Jika guru utama berhalangan, guru aktif diambil dari pengganti aktif.
        $active ??= $this->primaryIsPresent($primaryStatus) ? null : $chain->where('status_penugasan', 'aktif')->last();
        $latest = $chain->last();

        if ($primaryStatus === self::BELUM_KONFIRMASI) {
            return (object) [
                'active_teacher_id' => null,
                'active_replacement' => null,
                'latest_replacement' => $latest,
                'needs_replacement' => false,
            ];
        }

        return (object) [
            'active_teacher_id' => $this->primaryIsPresent($primaryStatus) ? $primaryTeacherId : ($active ? (int) $active->guru_pengganti_id : null),
            'active_replacement' => $this->primaryIsPresent($primaryStatus) ? null : $active,
            'latest_replacement' => $latest,
            'needs_replacement' => ! $this->primaryIsPresent($primaryStatus) && ! $active && (! $latest || $latest->status_penugasan === 'berhalangan'),
        ];
    }

    public function statusLabel(?string $status): string
    {
        // Mengubah status database menjadi label yang mudah dibaca user.
        return [
            'normal' => 'Hadir',
            'hadir' => 'Hadir',
            self::HADIR_OTOMATIS => 'Hadir Otomatis',
            self::MENUNGGU_VERIFIKASI_ULANG => 'Menunggu Verifikasi Ulang',
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'inval' => 'Tidak Hadir',
            'digantikan' => 'Digantikan',
            'aktif' => 'Aktif',
            'belum_konfirmasi' => 'Belum Konfirmasi',
            'berhalangan' => 'Berhalangan',
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
        ][$status ?: 'normal'] ?? ucwords(str_replace('_', ' ', (string) $status));
    }

    public function resolvePrimaryStatus(object $schedule, ?object $daily, string $date, ?CarbonInterface $at = null): object
    {
        $matchesDate = $this->scheduleMatchesDate($schedule, $date);
        $manual = ! empty($daily?->status_dipilih_at);
        $resetByAdmin = ! $manual && str_starts_with((string) ($daily?->alasan_tidak_hadir ?? ''), 'admin_reset:');
        $rawStatus = $manual
            ? ($daily?->status_guru ?: 'normal')
            : self::BELUM_KONFIRMASI;

        $effectiveStatus = $resetByAdmin ? self::MENUNGGU_VERIFIKASI_ULANG : $rawStatus;
        $source = $resetByAdmin ? 'admin_reset' : ($manual ? 'manual' : 'belum_konfirmasi');
        $reason = $resetByAdmin ? 'Verifikasi dibatalkan admin dan menunggu konfirmasi ulang.' : ($manual ? 'Status dipilih guru utama.' : 'Guru utama belum memilih status.');
        $automatic = false;
        $requiresAttention = ! $manual && $matchesDate;

        if (! $resetByAdmin && ! $manual && $matchesDate && $this->isPastCutoff($date, $at)) {
            $effectiveStatus = self::HADIR_OTOMATIS;
            $source = 'otomatis_cutoff';
            $reason = 'Lewat batas konfirmasi, guru utama dinyatakan hadir otomatis.';
            $automatic = true;
            $requiresAttention = false;
        }

        if (! $matchesDate) {
            $requiresAttention = false;
            $reason = 'Tanggal tidak sesuai hari mengajar.';
        }

        return (object) [
            'raw_status' => $rawStatus,
            'effective_status' => $effectiveStatus,
            'status_label' => $this->statusLabel($effectiveStatus),
            'status_source' => $source,
            'is_manual' => $manual,
            'is_automatic_cutoff' => $automatic,
            'requires_admin_attention' => $requiresAttention,
            'reason' => $reason,
        ];
    }

    private function primaryIsPresent(string $status): bool
    {
        return in_array($status, ['normal', self::HADIR_OTOMATIS], true);
    }

    private function scheduleMatchesDate(object $schedule, string $date): bool
    {
        if (empty($schedule->hari)) {
            return true;
        }

        $day = strtolower(Carbon::parse($date, 'Asia/Jakarta')->locale('id')->translatedFormat('l'));

        return strtolower((string) $schedule->hari) === $day;
    }

    public function roleLabel(?string $role): ?string
    {
        // Mengubah role guru aktif menjadi label tampilan.
        return [
            'guru_utama' => 'Guru Utama',
            'pengganti_pertama' => 'Guru Pengganti',
            'pengganti_lanjutan' => 'Guru Pengganti Lanjutan',
        ][$role ?: ''] ?? null;
    }

    public function ensureFirst(object $schedule, string $date, int $actorId, string $reason): ?object
    {
        // Jika jadwal tidak punya guru pengganti awal, tidak ada yang perlu dibuat.
        if (! $schedule->guru_pengganti_id) {
            return null;
        }

        // Membuat record pengganti pertama ketika guru utama berhalangan.
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
        // Guru pengganti mengonfirmasi apakah hadir atau berhalangan.
        DB::transaction(function () use ($scheduleId, $teacherId, $date, $attendance) {
            $row = DB::table('jadwal_guru_replacements')->where('jadwal_id', $scheduleId)->where('guru_pengganti_id', $teacherId)
                ->whereDate('tanggal', $date)->whereNull('deleted_at')->lockForUpdate()->orderByDesc('urutan_penggantian')->first();
            if (! $row) {
                return;
            }

            if ($attendance === 'hadir') {
                DB::table('jadwal_guru_replacements')->where('jadwal_id', $scheduleId)->whereDate('tanggal', $date)
                    ->where('id', '!=', $row->id)->where('status_penugasan', 'aktif')->whereNull('deleted_at')
                    ->update(['status_penugasan' => 'digantikan', 'selesai_at' => now('Asia/Jakarta'), 'updated_at' => now('Asia/Jakarta')]);
            }

            // Jika hadir, pengganti menjadi aktif. Jika tidak hadir, statusnya berhalangan.
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
        // Kandidat pengganti mengecualikan guru yang sudah dipakai di jadwal atau rantai pengganti.
        $used = DB::table('jadwal_guru_replacements')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->whereNull('deleted_at')
            ->pluck('guru_pengganti_id')->push($schedule->guru_id)->push($schedule->guru_pengganti_id)->filter()->unique();

        // Query ini memilih guru aktif yang tidak sedang izin/sakit dan tidak bentrok jadwal.
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
        // Sebelum menunjuk pengganti lanjutan, sistem memastikan guru tersebut tersedia dan tidak bentrok.
        abort_unless($this->candidates($schedule, $date)->contains('id', $teacherId), 422, 'Guru tidak tersedia atau memiliki jadwal bentrok.');

        // Pengganti lanjutan dibuat dalam transaksi agar rantai pengganti tetap rapi.
        return DB::transaction(function () use ($schedule, $date, $teacherId, $adminId, $reason) {
            $chain = DB::table('jadwal_guru_replacements')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->whereNull('deleted_at')->lockForUpdate()->orderBy('urutan_penggantian')->get();

            // Bagian ini menjaga data lama yang belum punya tabel replacement chain.
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

            // Menyimpan guru pengganti baru sebagai pengganti berikutnya dalam rantai.
            $id = DB::table('jadwal_guru_replacements')->insertGetId([
                'jadwal_id' => $schedule->id, 'tanggal' => $date, 'guru_utama_id' => $schedule->guru_id, 'guru_pengganti_id' => $teacherId,
                'menggantikan_replacement_id' => $previous?->id, 'urutan_penggantian' => $order, 'status_penugasan' => 'menunggu_konfirmasi',
                'alasan' => $reason, 'ditunjuk_oleh' => $adminId, 'created_at' => now('Asia/Jakarta'), 'updated_at' => now('Asia/Jakarta'),
            ]);
            if ($previous) {
                DB::table('jadwal_guru_replacements')->where('id', $previous->id)->update(['selesai_at' => now('Asia/Jakarta'), 'updated_at' => now('Asia/Jakarta')]);
            }
            DB::table('jadwal_pelajarans')->where('id', $schedule->id)->update(['guru_pengganti_id' => $teacherId, 'updated_at' => now('Asia/Jakarta')]);
            DB::table('jadwal_guru_statuses')->where('jadwal_id', $schedule->id)->whereDate('tanggal', $date)->update([
                'guru_pengganti_id' => $teacherId, 'pengganti_status' => null, 'pengganti_alasan' => null, 'pengganti_dipilih_at' => null, 'updated_at' => now('Asia/Jakarta'),
            ]);

            // Audit log mencatat penunjukan guru pengganti agar perubahan bisa ditelusuri.
            app(AttendanceAuditService::class)->record('assign_teaching_replacement', 'jadwal_guru_replacements', $id, $previous, DB::table('jadwal_guru_replacements')->where('id', $id)->first(), request(), $reason);

            return DB::table('jadwal_guru_replacements')->where('id', $id)->first();
        });
    }
}
