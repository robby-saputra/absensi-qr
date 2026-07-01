<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class SubjectAttendanceTeacherService
{
    public function __construct(private readonly ActiveTeachingTeacherResolver $resolver)
    {
    }

    public function resolve(object $schedule, string $date): object
    {
        // Resolver ini menentukan siapa guru yang benar-benar bertugas pada jadwal dan tanggal tertentu.
        // Hasilnya bisa guru utama atau guru pengganti, tergantung status hadir/izin/sakit.
        $state = $this->resolver->resolve((int) $schedule->id, $date);

        // Data dibuat dalam bentuk object sederhana agar controller mudah membaca status guru aktif.
        return (object) [
            'state' => $state,
            'guru_utama_id' => (int) ($schedule->guru_id ?? $state->schedule->guru_id),
            'guru_pelaksana_id' => $state->active_teacher_id ? (int) $state->active_teacher_id : null,
            'role_guru_pelaksana' => $state->active_teacher_role,
            'guru_pelaksana' => $state->active_teacher_name,
            'guru_tersedia' => ! empty($state->active_teacher_id),
            'butuh_pengganti' => (bool) $state->needs_replacement,
        ];
    }

    public function payload(object $schedule, string $date, bool $requireActiveTeacher = true): array
    {
        // Payload dipakai saat menyimpan absensi mapel.
        // Tujuannya agar absensi menyimpan guru utama dan guru pelaksana yang benar-benar bertugas.
        $resolved = $this->resolve($schedule, $date);

        // Jika wajib ada guru aktif tetapi resolver tidak menemukan guru aktif, proses dihentikan.
        if ($requireActiveTeacher && ! $resolved->guru_tersedia) {
            throw new \RuntimeException('Jadwal belum memiliki guru aktif.');
        }

        // Kolom dicek dulu supaya kode tetap aman jika migration di server belum lengkap.
        $payload = [];
        if (Schema::hasColumn('absensi_mapels', 'guru_utama_id')) {
            $payload['guru_utama_id'] = $resolved->guru_utama_id;
        }
        if (Schema::hasColumn('absensi_mapels', 'guru_pelaksana_id')) {
            $payload['guru_pelaksana_id'] = $resolved->guru_pelaksana_id;
        }
        if (Schema::hasColumn('absensi_mapels', 'role_guru_pelaksana')) {
            $payload['role_guru_pelaksana'] = $resolved->role_guru_pelaksana;
        }

        return $payload;
    }

    public function canManage(object $schedule, string $date, int $userId): bool
    {
        // Fungsi ini menjawab apakah guru tertentu boleh mengelola absensi mapel pada tanggal itu.
        // Hanya guru pelaksana aktif yang boleh mengelola sesi tersebut.
        $resolved = $this->resolve($schedule, $date);

        return $resolved->guru_pelaksana_id !== null
            && (int) $resolved->guru_pelaksana_id === $userId;
    }
}
