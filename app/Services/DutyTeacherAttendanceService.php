<?php

namespace App\Services;

use App\Models\GuruPiketStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DutyTeacherAttendanceService
{
    // Kumpulan status standar untuk guru piket harian.
    public const BELUM_KONFIRMASI = 'belum_konfirmasi';
    public const HADIR = 'hadir';
    public const IZIN = 'izin';
    public const SAKIT = 'sakit';
    public const DIGANTIKAN = 'digantikan';
    public const SELESAI = 'selesai';

    public function statusFor(int $guruPiketId, string $tanggal, ?int $guruId = null): ?GuruPiketStatus
    {
        // Mengambil status guru piket pada jadwal dan tanggal tertentu.
        // Jika guruId kosong, sistem mengambil status guru utama.
        return GuruPiketStatus::query()->where('guru_piket_id', $guruPiketId)
            ->when($guruId, fn ($query) => $query->where('guru_id', $guruId), fn ($query) => $query->where('peran', 'utama'))
            ->whereDate('tanggal', $tanggal)->first();
    }

    public function statusKey(int $guruPiketId, int $guruId): string
    {
        // Key gabungan ini dipakai untuk mengelompokkan status berdasarkan jadwal dan guru.
        return $guruPiketId.':'.$guruId;
    }

    public function statusesFor(array $guruPiketIds, string $tanggal): \Illuminate\Support\Collection
    {
        // Jika tidak ada jadwal piket yang dikirim, kembalikan collection kosong.
        if (empty($guruPiketIds)) {
            return collect();
        }

        // Mengambil banyak status sekaligus agar dashboard/rekap tidak query berulang-ulang.
        return GuruPiketStatus::query()
            ->whereIn('guru_piket_id', array_values(array_unique($guruPiketIds)))
            ->whereDate('tanggal', $tanggal)
            ->get()
            ->keyBy(fn (GuruPiketStatus $status) => $this->statusKey(
                (int) $status->guru_piket_id,
                (int) $status->guru_id
            ));
    }

    public function currentStatus(?object $dailyStatus): string
    {
        // Jika guru belum mengisi status, dianggap belum_konfirmasi.
        return $dailyStatus?->status ?: self::BELUM_KONFIRMASI;
    }

    public function hasConfirmed(?object $dailyStatus): bool
    {
        // Guru dianggap sudah konfirmasi jika statusnya hadir/izin/sakit dan waktu konfirmasi terisi.
        return in_array($this->currentStatus($dailyStatus), [self::HADIR, self::IZIN, self::SAKIT], true)
            && $dailyStatus?->waktu_konfirmasi !== null;
    }

    public function isReplacementActive(?object $primaryStatus): bool
    {
        // Pengganti dibutuhkan saat guru utama piket berstatus izin atau sakit.
        return in_array($primaryStatus?->status, [self::IZIN, self::SAKIT], true);
    }

    public function labelFor(object $jadwal, string $tanggal): string
    {
        // Label ini dipakai untuk menampilkan status yang mudah dibaca di dashboard.
        $status = property_exists($jadwal, 'status_harian')
            ? $jadwal->status_harian
            : $this->statusFor((int) $jadwal->id, $tanggal)?->status;
        if (! $status) {
            return 'Belum Konfirmasi';
        }

        // Jika status hadir tetapi jam tugas sudah lewat, labelnya menjadi selesai.
        if ($status === self::HADIR && $jadwal->jam_selesai && Carbon::parse($tanggal.' '.$jadwal->jam_selesai)->isPast()) {
            return 'Selesai';
        }

        // Mengubah status database menjadi label bahasa manusia untuk tampilan.
        return match ($status) {
            self::HADIR => 'Sedang Bertugas',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit',
            self::DIGANTIKAN => 'Digantikan',
            self::SELESAI => 'Selesai',
            default => 'Belum Konfirmasi',
        };
    }

    public function statusLabel(?string $status, ?string $source = null): string
    {
        return match ($status) {
            'terjadwal' => 'Terjadwal',
            self::HADIR => $source === 'system_cutoff' ? 'Hadir Otomatis' : 'Hadir',
            'hadir_otomatis' => 'Hadir Otomatis',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit',
            self::DIGANTIKAN => 'Digantikan',
            self::SELESAI => 'Selesai',
            'aktif' => 'Pengganti Aktif',
            'berhalangan' => 'Berhalangan',
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
            default => 'Belum Konfirmasi',
        };
    }

    public function normalizeDayName(?string $hari): string
    {
        return str_replace(["'", ' '], ['', ''], strtolower(trim((string) $hari)));
    }

    public function dayNameForDate(string $tanggal): string
    {
        $index = (int) Carbon::parse($tanggal, 'Asia/Jakarta')->dayOfWeek;

        return [
            0 => 'minggu',
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
        ][$index] ?? '';
    }

    public function scheduleMatchesDate(object $schedule, string $tanggal): bool
    {
        return $this->normalizeDayName($schedule->hari ?? '') === $this->dayNameForDate($tanggal);
    }

    public function effectiveStatus(?object $dailyStatus, string $tanggal, ?object $schedule = null, ?Carbon $at = null): string
    {
        $raw = $this->currentStatus($dailyStatus);

        if ($raw !== self::BELUM_KONFIRMASI) {
            return $raw;
        }

        if ($schedule && ! $this->scheduleMatchesDate($schedule, $tanggal)) {
            return 'terjadwal';
        }

        $at ??= now('Asia/Jakarta');
        $today = $at->copy()->toDateString();
        $pastCutoff = app(DutyTeacherAssignmentService::class)->isPastCutoff($at);

        if ($tanggal !== $today) {
            return 'terjadwal';
        }

        return $tanggal === $today && $pastCutoff ? 'hadir_otomatis' : self::BELUM_KONFIRMASI;
    }

    public function buildDutyState(object $schedule, string $tanggal, ?\Illuminate\Support\Collection $statusRows = null, ?\Illuminate\Support\Collection $replacementChain = null): object
    {
        $statusRows ??= $this->statusesFor([(int) $schedule->id], $tanggal);
        $replacementChain ??= DB::table('guru_piket_replacements as r')
            ->join('users as pengganti', 'pengganti.id', '=', 'r.guru_pengganti_id')
            ->where('r.guru_piket_id', $schedule->id)
            ->whereDate('r.tanggal', $tanggal)
            ->whereNull('r.deleted_at')
            ->select('r.*', 'pengganti.nama as nama_pengganti_rantai')
            ->orderBy('r.urutan_penggantian')
            ->get();

        $primaryStatus = $statusRows->get($this->statusKey((int) $schedule->id, (int) $schedule->guru_id));
        $primaryRaw = $this->currentStatus($primaryStatus);
        $matchesDate = $this->scheduleMatchesDate($schedule, $tanggal);
        $targetDate = Carbon::parse($tanggal, 'Asia/Jakarta');
        $now = now('Asia/Jakarta');
        $isTargetToday = $targetDate->isSameDay($now);
        $shiftStarted = false;
        $shiftEnded = false;
        $isOnDutyNow = false;

        if ($matchesDate && $isTargetToday && ! empty($schedule->jam_mulai) && ! empty($schedule->jam_selesai)) {
            $shiftStart = Carbon::parse($tanggal.' '.$schedule->jam_mulai, 'Asia/Jakarta');
            $shiftEnd = Carbon::parse($tanggal.' '.$schedule->jam_selesai, 'Asia/Jakarta');
            $shiftStarted = $now->gte($shiftStart);
            $shiftEnded = $now->gt($shiftEnd);
            $isOnDutyNow = $now->betweenIncluded($shiftStart, $shiftEnd);
        }

        $primaryEffective = $this->effectiveStatus($primaryStatus, $tanggal, $schedule, $now);
        $primaryName = $schedule->nama_guru_utama ?? $schedule->guru_utama ?? $schedule->nama ?? '-';
        $replacementName = $schedule->nama_guru_pengganti ?? $schedule->nama_pengganti ?? null;

        $activeReplacement = $replacementChain->where('status_penugasan', 'aktif')->last();
        $latestRelevantReplacement = $replacementChain
            ->filter(fn ($row) => in_array($row->status_penugasan, ['menunggu_konfirmasi', 'aktif', 'berhalangan'], true))
            ->last();

        $replacementStatus = null;
        if ($latestRelevantReplacement?->guru_pengganti_id) {
            $replacementStatus = $statusRows->get($this->statusKey((int) $schedule->id, (int) $latestRelevantReplacement->guru_pengganti_id));
        } elseif ($schedule->guru_pengganti_id ?? null) {
            $replacementStatus = $statusRows->get($this->statusKey((int) $schedule->id, (int) $schedule->guru_pengganti_id));
        }

        $replacementRaw = $replacementStatus?->status
            ?: ($latestRelevantReplacement?->status_penugasan ?: self::BELUM_KONFIRMASI);
        $replacementEffective = $replacementStatus
            ? $this->effectiveStatus($replacementStatus, $tanggal, $schedule, $now)
            : $replacementRaw;

        $primaryUnavailable = in_array($primaryEffective, [self::IZIN, self::SAKIT, self::DIGANTIKAN, self::SELESAI], true);
        if (! $activeReplacement && $primaryUnavailable && $latestRelevantReplacement && in_array($replacementEffective, [self::HADIR, 'hadir_otomatis'], true)) {
            $activeReplacement = $latestRelevantReplacement;
        }

        $activeTeacherId = null;
        $activeTeacherName = null;
        $activeRole = null;

        if ($activeReplacement) {
            $activeTeacherId = (int) $activeReplacement->guru_pengganti_id;
            $activeTeacherName = $activeReplacement->nama_pengganti_rantai
                ?? $activeReplacement->nama_pengganti
                ?? null;
            $activeRole = ((int) $activeReplacement->urutan_penggantian === 1) ? 'pengganti_pertama' : 'pengganti_lanjutan';
        } elseif (! $primaryUnavailable) {
            $activeTeacherId = $matchesDate ? (int) $schedule->guru_id : null;
            $activeTeacherName = $primaryName;
            $activeRole = $matchesDate ? 'utama' : null;
        }

        $waitingReplacement = $primaryUnavailable && ! $activeReplacement;

        return (object) [
            'schedule' => $schedule,
            'date' => $tanggal,
            'primary_raw_status' => $primaryRaw,
            'primary_effective_status' => $primaryEffective,
            'primary_status_label' => $this->statusLabel($primaryEffective, $primaryStatus?->sumber),
            'primary_raw_label' => $this->statusLabel($primaryRaw, $primaryStatus?->sumber),
            'primary_status_source' => $primaryStatus?->sumber,
            'schedule_matches_date' => $matchesDate,
            'shift_started' => $shiftStarted,
            'shift_ended' => $shiftEnded,
            'is_on_duty_now' => $isOnDutyNow,
            'primary_name' => $primaryName,
            'replacement_name' => $replacementName,
            'replacement_raw_status' => $replacementRaw,
            'replacement_effective_status' => $replacementEffective,
            'replacement_status_label' => $this->statusLabel($replacementEffective, $replacementStatus?->sumber),
            'replacement_chain' => $replacementChain,
            'active_teacher_id' => $activeTeacherId,
            'active_teacher_name' => $activeTeacherName,
            'active_role' => $activeRole,
            'active_label' => $activeTeacherName ?: ($waitingReplacement ? 'Menunggu konfirmasi' : 'Belum tersedia'),
            'waiting_replacement' => $waitingReplacement,
        ];
    }

    public function resolveQrAvailability(?object $dutyState, ?object $user, ?object $holiday = null, ?Carbon $at = null): object
    {
        $at ??= now('Asia/Jakarta');

        if ($holiday) {
            return (object) ['can_manage' => false, 'reason' => 'Hari ini merupakan hari libur sekolah.', 'status' => 'libur'];
        }

        if (! $dutyState) {
            return (object) ['can_manage' => false, 'reason' => 'Tidak terdapat jadwal Guru Piket pada hari ini.', 'status' => 'tidak_ada_jadwal'];
        }

        $schedule = $dutyState->schedule;
        if (! $this->scheduleMatchesDate($schedule, $dutyState->date)) {
            return (object) ['can_manage' => false, 'reason' => 'Tidak terdapat jadwal Guru Piket pada hari ini.', 'status' => 'bukan_hari_jadwal'];
        }

        if (! $at->isSameDay(Carbon::parse($dutyState->date, 'Asia/Jakarta'))) {
            return (object) ['can_manage' => false, 'reason' => 'QR hanya dapat dikelola pada tanggal tugas berjalan.', 'status' => 'bukan_tanggal_tugas'];
        }

        if (empty($schedule->jam_mulai) || empty($schedule->jam_selesai)) {
            return (object) ['can_manage' => false, 'reason' => 'Jam tugas piket belum lengkap.', 'status' => 'jam_tidak_lengkap'];
        }

        $start = Carbon::parse($dutyState->date.' '.$schedule->jam_mulai, 'Asia/Jakarta');
        $end = Carbon::parse($dutyState->date.' '.$schedule->jam_selesai, 'Asia/Jakarta');

        if ($at->lt($start)) {
            return (object) ['can_manage' => false, 'reason' => 'Jam tugas piket belum dimulai.', 'status' => 'belum_mulai'];
        }

        if ($at->gt($end)) {
            return (object) ['can_manage' => false, 'reason' => 'Jam tugas piket hari ini telah berakhir.', 'status' => 'selesai'];
        }

        if (! $dutyState->active_teacher_id) {
            return (object) ['can_manage' => false, 'reason' => $dutyState->waiting_replacement ? 'Menunggu konfirmasi Guru Pengganti.' : 'Belum terdapat petugas aktif.', 'status' => 'menunggu_petugas'];
        }

        if (($user->role ?? null) === 'piket') {
            return (object) ['can_manage' => true, 'reason' => null, 'status' => 'aktif'];
        }

        if (($user->role ?? null) === 'guru' && (int) $dutyState->active_teacher_id === (int) $user->id) {
            return (object) ['can_manage' => true, 'reason' => null, 'status' => 'aktif'];
        }

        return (object) ['can_manage' => false, 'reason' => 'Anda bukan petugas aktif pada jadwal piket ini.', 'status' => 'bukan_petugas_aktif'];
    }

    public function confirm(int $guruPiketId, string $tanggal, string $status, int $actorId, string $source = 'web', ?string $note = null, string $role = 'utama', ?int $replacingTeacherId = null): GuruPiketStatus
    {
        // Status yang boleh disimpan dibatasi agar nilai database tetap konsisten.
        abort_unless(in_array($status, [self::HADIR, self::IZIN, self::SAKIT, self::DIGANTIKAN, self::SELESAI], true), 422, 'Status guru piket tidak valid.');

        // Transaksi dipakai agar proses konfirmasi tidak dobel jika tombol ditekan berulang.
        return DB::transaction(function () use ($guruPiketId, $tanggal, $status, $actorId, $source, $note, $role, $replacingTeacherId) {
            // lockForUpdate mengunci status harian guru tersebut saat sedang diproses.
            $existing = GuruPiketStatus::query()->where('guru_piket_id', $guruPiketId)->where('guru_id', $actorId)->whereDate('tanggal', $tanggal)->lockForUpdate()->first();
            abort_if($existing && $existing->waktu_konfirmasi, 422, 'Status guru piket pada tanggal ini sudah dikonfirmasi.');

            // updateOrCreate membuat status baru atau memperbarui status yang belum dikonfirmasi.
            return GuruPiketStatus::query()->updateOrCreate(
                ['guru_piket_id' => $guruPiketId, 'guru_id' => $actorId, 'tanggal' => $tanggal],
                ['status' => $status, 'peran' => $role, 'menggantikan_guru_id' => $replacingTeacherId, 'waktu_konfirmasi' => now(), 'dipilih_oleh' => $actorId, 'sumber' => $source, 'keterangan' => $note]
            );
        });
    }
}
