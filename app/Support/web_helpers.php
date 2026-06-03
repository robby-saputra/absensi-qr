<?php

use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! function_exists('jamBentrok')) {
    function jamBentrok($query, string $jamMulai, string $jamSelesai)
    {
        return $query
            ->where('jam_mulai', '<', $jamSelesai)
            ->where('jam_selesai', '>', $jamMulai);
    }
}

if (! function_exists('tahunAjaranAktifId')) {
    function tahunAjaranAktifId()
    {
        return DB::table('tahun_ajarans')->where('aktif', true)->value('id');
    }
}

if (! function_exists('wajibSuperadmin')) {
    function wajibSuperadmin(): void
    {
        $user = session('user');
        $level = $user ? DB::table('users')->where('id', $user->id)->value('admin_level') : null;

        abort_if(! $user || $user->role !== 'admin' || $level !== 'superadmin', 403, 'Hanya superadmin yang boleh mengakses fitur ini.');
    }
}

if (! function_exists('akunAktifRole')) {
    function akunAktifRole(int|string|null $id, string $role): bool
    {
        if (! $id) {
            return true;
        }

        return User::where('id', $id)
            ->where('role', $role)
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->exists();
    }
}

if (! function_exists('validasiGuruAktifIds')) {
    function validasiGuruAktifIds(array $ids): ?string
    {
        $ids = collect($ids)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return null;
        }

        $aktif = User::whereIn('id', $ids)
            ->where('role', 'guru')
            ->where('aktif', 1)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $nonaktif = $ids->diff($aktif)->values();

        if ($nonaktif->isEmpty()) {
            return null;
        }

        $nama = User::whereIn('id', $nonaktif)
            ->pluck('nama')
            ->filter()
            ->implode(', ');

        return 'Guru nonaktif tidak bisa dipilih untuk tugas operasional'.($nama ? ': '.$nama : '.');
    }
}

if (! function_exists('siswaAktifQuery')) {
    function siswaAktifQuery()
    {
        return User::where('role', 'siswa')
            ->where('aktif', 1)
            ->whereNull('deleted_at');
    }
}

if (! function_exists('buatNotifikasi')) {
    function buatNotifikasi(array $data): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $payload = [
            'user_id' => $data['user_id'] ?? null,
            'judul' => $data['judul'] ?? 'Notifikasi',
            'pesan' => $data['pesan'] ?? null,
            'status' => $data['status'] ?? 'belum_dibaca',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach (['kategori', 'severity', 'source_type', 'source_id', 'payload'] as $column) {
            if (Schema::hasColumn('notifications', $column)) {
                $payload[$column] = $column === 'payload'
                    ? json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE)
                    : ($data[$column] ?? ($column === 'severity' ? 'info' : null));
            }
        }

        DB::table('notifications')->insert($payload);
    }
}

if (! function_exists('absensiTerkunci')) {
    function absensiTerkunci(string $jenis, string $tanggal, ?int $jadwalId = null, ?int $kelasId = null): ?object
    {
        if (! Schema::hasTable('attendance_session_locks')) {
            return null;
        }

        return DB::table('attendance_session_locks')
            ->where('jenis', $jenis)
            ->whereDate('tanggal', $tanggal)
            ->when($jadwalId, fn ($query) => $query->where('jadwal_id', $jadwalId), fn ($query) => $query->whereNull('jadwal_id'))
            ->when($kelasId, fn ($query) => $query->where('kelas_id', $kelasId), fn ($query) => $query->whereNull('kelas_id'))
            ->first();
    }
}

if (! function_exists('simpanKunciAbsensi')) {
    function simpanKunciAbsensi(string $jenis, string $tanggal, ?int $jadwalId, ?int $kelasId, ?string $catatan, Request $request): void
    {
        if (! Schema::hasTable('attendance_session_locks')) {
            return;
        }

        $user = session('user');
        DB::table('attendance_session_locks')->updateOrInsert(
            [
                'jenis' => $jenis,
                'tanggal' => $tanggal,
                'jadwal_id' => $jadwalId,
                'kelas_id' => $kelasId,
            ],
            [
                'locked_by' => $user?->id,
                'status' => 'final',
                'catatan' => $catatan,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $lock = absensiTerkunci($jenis, $tanggal, $jadwalId, $kelasId);
        if ($lock) {
            AuditLogger::record('create', 'attendance_session_locks', (int) $lock->id, 'Absensi difinalisasi', null, $lock, $request);
        }
    }
}

if (! function_exists('kelasAksesGuruIds')) {
    function kelasAksesGuruIds(int $guruId)
    {
        return DB::table('jadwal_pelajarans')
            ->where(function ($query) use ($guruId) {
                $query->where('guru_id', $guruId)
                    ->orWhere('guru_pengganti_id', $guruId);
            })
            ->pluck('kelas_id')
            ->unique()
            ->values();
    }
}

if (! function_exists('buatNotifikasiRoleHarian')) {
    function buatNotifikasiRoleHarian(int $userId, string $kategori, string $judul, string $pesan, array $payload = []): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $lockPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $exists = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('kategori', $kategori)
            ->whereDate('created_at', now()->toDateString())
            ->where('payload', $lockPayload)
            ->exists();

        if (! $exists) {
            buatNotifikasi([
                'user_id' => $userId,
                'judul' => $judul,
                'pesan' => $pesan,
                'kategori' => $kategori,
                'severity' => 'info',
                'payload' => $payload,
            ]);
        }
    }
}

if (! function_exists('periodeBulan')) {
    function periodeBulan(?string $bulan = null): array
    {
        $start = Carbon::parse(($bulan ?: now()->format('Y-m')).'-01')->startOfMonth();

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString(), $start->format('Y-m')];
    }
}

if (! function_exists('validasiDataTutupBulan')) {
    function validasiDataTutupBulan(string $mulai, string $selesai, ?int $kelasId = null, ?int $tahunAjaranId = null): array
    {
        $siswaQuery = DB::table('users')->where('role', 'siswa')->where('aktif', 1)->whereNull('deleted_at');
        if ($kelasId) {
            $siswaQuery->where('kelas_id', $kelasId);
        }
        $siswaIds = $siswaQuery->pluck('id');

        $belumPulang = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->whereNull('a.deleted_at')
            ->whereBetween('a.tanggal', [$mulai, $selesai])
            ->whereIn('a.id_siswa', $siswaIds)
            ->when($tahunAjaranId, fn ($q) => $q->where('a.tahun_ajaran_id', $tahunAjaranId))
            ->whereNotNull('a.jam_masuk')
            ->whereNull('a.jam_pulang')
            ->whereNotIn(DB::raw('COALESCE(a.status_pulang,"")'), ['izin', 'sakit', 'alfa', 'alpa'])
            ->select('a.id', 'a.tanggal', 's.nama', 'k.nama_kelas', 'a.jam_masuk')
            ->limit(200)
            ->get();

        $alfaBelumDiproses = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->whereNull('a.deleted_at')
            ->whereBetween('a.tanggal', [$mulai, $selesai])
            ->whereIn('a.id_siswa', $siswaIds)
            ->when($tahunAjaranId, fn ($q) => $q->where('a.tahun_ajaran_id', $tahunAjaranId))
            ->where(function ($q) {
                $q->whereIn('a.status_masuk', ['alfa', 'alpa'])->orWhereIn('a.status_pulang', ['alfa', 'alpa']);
            })
            ->select('a.id', 'a.tanggal', 's.nama', 'k.nama_kelas', 'a.status_masuk', 'a.status_pulang')
            ->limit(200)
            ->get();

        $izinBelumReview = Schema::hasTable('student_permit_requests')
            ? DB::table('student_permit_requests as p')
                ->join('users as s', 's.id', '=', 'p.siswa_id')
                ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
                ->whereNull('p.deleted_at')
                ->where('p.status', 'menunggu')
                ->where(function ($q) use ($mulai, $selesai) {
                    $q->whereBetween('p.tanggal_mulai', [$mulai, $selesai])->orWhereBetween('p.tanggal_selesai', [$mulai, $selesai]);
                })
                ->whereIn('p.siswa_id', $siswaIds)
                ->select('p.id', 'p.tanggal_mulai', 'p.tanggal_selesai', 'p.jenis', 's.nama', 'k.nama_kelas')
                ->limit(200)
                ->get()
            : collect();

        $belumMapel = DB::table('jadwal_pelajarans as j')
            ->join('users as s', function ($join) {
                $join->on('s.kelas_id', '=', 'j.kelas_id')->where('s.role', 'siswa')->where('s.aktif', 1);
            })
            ->leftJoin('absensi_mapels as am', function ($join) use ($mulai, $selesai) {
                $join->on('am.jadwal_id', '=', 'j.id')
                    ->on('am.siswa_id', '=', 's.id')
                    ->whereBetween('am.tanggal', [$mulai, $selesai])
                    ->whereNull('am.deleted_at');
            })
            ->leftJoin('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->leftJoin('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->whereIn('s.id', $siswaIds)
            ->whereNull('j.deleted_at')
            ->where(function ($query) {
                $query->whereNull('am.id')->orWhereNull('am.deleted_at');
            })
            ->when($tahunAjaranId, fn ($q) => $q->where('j.tahun_ajaran_id', $tahunAjaranId))
            ->whereNull('am.id')
            ->select('j.id', 's.nama', 'k.nama_kelas', 'm.nama_mapel', 'j.hari', 'j.jam_mulai', 'j.jam_selesai')
            ->limit(200)
            ->get();

        return compact('belumPulang', 'belumMapel', 'alfaBelumDiproses', 'izinBelumReview');
    }
}

if (! function_exists('scopeValidasiBulanan')) {
    function scopeValidasiBulanan(?int $kelasId): string
    {
        return $kelasId ? 'kelas' : 'sekolah';
    }
}

if (! function_exists('statusValidasiBulanan')) {
    function statusValidasiBulanan(string $periode, ?int $tahunAjaranId, ?int $kelasId): ?object
    {
        if (! Schema::hasTable('monthly_validation_statuses')) {
            return null;
        }

        return DB::table('monthly_validation_statuses')
            ->where('periode', $periode)
            ->where('scope', scopeValidasiBulanan($kelasId))
            ->when($tahunAjaranId, fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId), fn ($q) => $q->whereNull('tahun_ajaran_id'))
            ->when($kelasId, fn ($q) => $q->where('kelas_id', $kelasId), fn ($q) => $q->whereNull('kelas_id'))
            ->first();
    }
}

if (! function_exists('simpanStatusValidasiBulanan')) {
    function simpanStatusValidasiBulanan(string $periode, ?int $tahunAjaranId, ?int $kelasId, array $hasil, Request $request): object
    {
        $totalMasalah = collect($hasil)->sum(fn ($items) => $items->count());
        $ringkasan = [
            'belum_pulang' => $hasil['belumPulang']->count(),
            'belum_mapel' => $hasil['belumMapel']->count(),
            'alfa' => $hasil['alfaBelumDiproses']->count(),
            'izin_menunggu' => $hasil['izinBelumReview']->count(),
        ];
        $status = $totalMasalah > 0 ? 'ada_masalah' : 'valid';
        $user = session('user');

        DB::table('monthly_validation_statuses')->updateOrInsert(
            [
                'tahun_ajaran_id' => $tahunAjaranId,
                'kelas_id' => $kelasId,
                'periode' => $periode,
                'scope' => scopeValidasiBulanan($kelasId),
            ],
            [
                'status' => $status,
                'total_masalah' => $totalMasalah,
                'ringkasan' => json_encode($ringkasan, JSON_UNESCAPED_UNICODE),
                'checked_by' => $user?->id,
                'checked_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $row = statusValidasiBulanan($periode, $tahunAjaranId, $kelasId);
        AuditLogger::record('monthly_validation_check', 'monthly_validation_statuses', (int) $row->id, 'Validasi tutup bulan dicek', null, $row, $request);

        return $row;
    }
}

if (! function_exists('buatNotifikasiBulanBelumDitutup')) {
    function buatNotifikasiBulanBelumDitutup(): void
    {
        if (! Schema::hasTable('monthly_validation_statuses') || ! Schema::hasTable('notifications')) {
            return;
        }

        $periode = now()->subMonthNoOverflow()->format('Y-m');
        $tahunAjaranId = tahunAjaranAktifId();
        $terkunci = DB::table('monthly_validation_statuses')
            ->where('periode', $periode)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->where('status', 'dikunci')
            ->exists();

        if ($terkunci || now()->day < 1) {
            return;
        }

        $admins = User::where('role', 'admin')->pluck('id');
        foreach ($admins as $adminId) {
            buatNotifikasiRoleHarian((int) $adminId, 'bulan_belum_ditutup', 'Bulan Belum Ditutup', 'Data periode '.$periode.' belum dikunci. Silakan cek Validasi Tutup Bulan.', ['periode' => $periode]);
        }
    }
}

if (! function_exists('tabelBisaArsip')) {
    function tabelBisaArsip(): array
    {
        return [
            'users' => 'User',
            'kelas' => 'Kelas',
            'jurusan' => 'Jurusan',
            'jadwal_pelajarans' => 'Jadwal Pelajaran',
            'guru_pikets' => 'Guru Piket',
            'absensis' => 'Absensi Harian',
            'absensi_mapels' => 'Absensi Mapel',
            'announcements' => 'Pengumuman',
            'kalender_sekolahs' => 'Kalender Sekolah',
            'tahun_ajarans' => 'Tahun Ajaran',
        ];
    }
}

if (! function_exists('arsipkanData')) {
    function arsipkanData(string $table, int $id, string $judul, Request $request): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $before = DB::table($table)->where('id', $id)->first();
        if (! $before) {
            return false;
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $payload = ['deleted_at' => now()];
            if (Schema::hasColumn($table, 'updated_at')) {
                $payload['updated_at'] = now();
            }
            DB::table($table)->where('id', $id)->update($payload);
            $after = DB::table($table)->where('id', $id)->first();
            AuditLogger::record('soft_delete', $table, $id, $judul.' diarsipkan', $before, $after, $request);
        } else {
            DB::table($table)->where('id', $id)->delete();
            AuditLogger::record('delete', $table, $id, $judul.' dihapus permanen', $before, null, $request);
        }

        return true;
    }
}

if (! function_exists('hapusMassalAdmin')) {
    function hapusMassalAdmin(string $resource, array $ids, Request $request): array
    {
        $resources = [
            'kalender-sekolah' => ['table' => 'kalender_sekolahs', 'label' => 'Kalender sekolah'],
            'pengumuman' => ['table' => 'announcements', 'label' => 'Pengumuman'],
            'tahun-ajaran' => ['table' => 'tahun_ajarans', 'label' => 'Tahun ajaran'],
            'absensi' => ['table' => 'absensis', 'label' => 'Absensi harian'],
            'absensi-mapel' => ['table' => 'absensi_mapels', 'label' => 'Absensi mapel'],
            'users' => ['table' => 'users', 'label' => 'User'],
            'guru' => ['table' => 'users', 'label' => 'Data guru'],
            'kelas' => ['table' => 'kelas', 'label' => 'Data kelas'],
            'jadwal' => ['table' => 'jadwal_pelajarans', 'label' => 'Jadwal pelajaran'],
            'siswa' => ['table' => 'users', 'label' => 'Data siswa'],
            'guru-piket' => ['table' => 'guru_pikets', 'label' => 'Guru piket'],
            'jurusan' => ['table' => 'jurusan', 'label' => 'Jurusan'],
        ];

        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return ['deleted' => 0, 'skipped' => 0, 'message' => 'Pilih minimal satu data.'];
        }

        if ($resource === 'wali-kelas') {
            $updated = DB::table('kelas')->whereIn('id', $ids)->update([
                'wali_kelas_id' => null,
                'updated_at' => now(),
            ]);

            return ['deleted' => $updated, 'skipped' => $ids->count() - $updated, 'message' => $updated.' wali kelas berhasil dihapus.'];
        }

        abort_if(! isset($resources[$resource]), 404);

        $config = $resources[$resource];
        $deleted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            if ($resource === 'tahun-ajaran' && DB::table('tahun_ajarans')->where('id', $id)->where('aktif', true)->exists()) {
                $skipped++;

                continue;
            }

            if (in_array($resource, ['users', 'guru', 'siswa'], true)) {
                $query = DB::table('users')->where('id', $id);
                if ($resource === 'guru') {
                    $query->where('role', 'guru');
                } elseif ($resource === 'siswa') {
                    $query->where('role', 'siswa');
                }

                $user = $query->first();
                if (! $user || ($user->role === 'admin' && ($user->admin_level ?? null) === 'superadmin')) {
                    $skipped++;

                    continue;
                }
            }

            if (arsipkanData($config['table'], $id, $config['label'], $request)) {
                $deleted++;
            } else {
                $skipped++;
            }
        }

        return [
            'deleted' => $deleted,
            'skipped' => $skipped,
            'message' => $deleted.' data berhasil dihapus'.($skipped ? ', '.$skipped.' data dilewati.' : '.'),
        ];
    }
}

if (! function_exists('tanpaArsip')) {
    function tanpaArsip($query, string $table, ?string $alias = null)
    {
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull(($alias ?: $table).'.deleted_at');
        }

        return $query;
    }
}

if (! function_exists('rekapTerkunci')) {
    function rekapTerkunci(string $jenis, string $tanggal): ?object
    {
        if (! Schema::hasTable('rekap_locks')) {
            return null;
        }

        return DB::table('rekap_locks')
            ->whereNull('deleted_at')
            ->where('jenis_rekap', $jenis)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->first();
    }
}

if (! function_exists('jalankanAutoAlfaHarian')) {
    function jalankanAutoAlfaHarian(?string $tanggal = null): array
    {
        $tanggal ??= now()->toDateString();
        $libur = hariLiburSekolah($tanggal);
        if ($libur) {
            return ['created' => 0, 'skipped' => 'libur'];
        }

        if (rekapTerkunci('absensi_harian', $tanggal)) {
            return ['created' => 0, 'skipped' => 'terkunci'];
        }

        $tahunAjaranId = tahunAjaranAktifId();
        $status = AttendanceSettingService::statusDefaultAlfa();
        $created = 0;
        $siswa = siswaAktifQuery()->get();

        foreach ($siswa as $row) {
            $exists = DB::table('absensis')->where('id_siswa', $row->id)->whereDate('tanggal', $tanggal)->whereNull('deleted_at')->exists();
            if ($exists) {
                continue;
            }

            DB::table('absensis')->insert([
                'tahun_ajaran_id' => $tahunAjaranId,
                'id_siswa' => $row->id,
                'tanggal' => $tanggal,
                'jam_masuk' => null,
                'jam_pulang' => null,
                'status_masuk' => $status,
                'status_pulang' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => null];
    }
}

if (! function_exists('prosesReviewPengajuanSiswa')) {
    function prosesReviewPengajuanSiswa(int $id, string $status, ?string $catatan, Request $request): array
    {
        $old = DB::table('student_permit_requests')->where('id', $id)->whereNull('deleted_at')->first();
        abort_if(! $old, 404);

        DB::table('student_permit_requests')->where('id', $id)->update([
            'status' => $status,
            'catatan_review' => $catatan,
            'reviewed_by' => session('user')->id,
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        $createdHarian = 0;
        $createdMapel = 0;
        $notifiedGuru = collect();

        if ($status === 'disetujui') {
            $siswa = siswaAktifQuery()->findOrFail($old->siswa_id);
            $period = \Carbon\CarbonPeriod::create($old->tanggal_mulai, $old->tanggal_selesai);

            foreach ($period as $date) {
                $tanggal = $date->toDateString();
                if (rekapTerkunci('absensi_harian', $tanggal)) {
                    continue;
                }

                $existingHarian = DB::table('absensis')->where('id_siswa', $old->siswa_id)->whereDate('tanggal', $tanggal)->whereNull('deleted_at')->first();
                $payloadHarian = [
                    'tahun_ajaran_id' => tahunAjaranAktifId(),
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'status_masuk' => $old->jenis,
                    'status_pulang' => $old->jenis,
                    'updated_at' => now(),
                ];

                if ($existingHarian) {
                    DB::table('absensis')->where('id', $existingHarian->id)->update($payloadHarian);
                } else {
                    DB::table('absensis')->insert($payloadHarian + [
                        'id_siswa' => $old->siswa_id,
                        'tanggal' => $tanggal,
                        'created_at' => now(),
                    ]);
                    $createdHarian++;
                }

                $hari = strtolower(Carbon::parse($tanggal)->locale('id')->translatedFormat('l'));
                $jadwals = DB::table('jadwal_pelajarans')
                    ->where('kelas_id', $siswa->kelas_id)
                    ->whereRaw('LOWER(hari) = ?', [$hari])
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($jadwals as $jadwal) {
                    $existingMapel = DB::table('absensi_mapels')
                        ->where('jadwal_id', $jadwal->id)
                        ->where('siswa_id', $old->siswa_id)
                        ->whereDate('tanggal', $tanggal)
                        ->whereNull('deleted_at')
                        ->first();
                    $payloadMapel = [
                        'tahun_ajaran_id' => $jadwal->tahun_ajaran_id ?? tahunAjaranAktifId(),
                        'jam_scan' => null,
                        'status' => $old->jenis,
                        'updated_at' => now(),
                    ];

                    if ($existingMapel) {
                        DB::table('absensi_mapels')->where('id', $existingMapel->id)->update($payloadMapel);
                    } else {
                        DB::table('absensi_mapels')->insert($payloadMapel + [
                            'jadwal_id' => $jadwal->id,
                            'siswa_id' => $old->siswa_id,
                            'tanggal' => $tanggal,
                            'created_at' => now(),
                        ]);
                        $createdMapel++;
                    }

                    foreach (array_filter([$jadwal->guru_id, $jadwal->guru_pengganti_id]) as $guruId) {
                        if ($notifiedGuru->contains($guruId)) {
                            continue;
                        }

                        buatNotifikasi([
                            'user_id' => $guruId,
                            'judul' => 'Pengajuan Izin/Sakit Disetujui',
                            'pesan' => $siswa->nama.' '.$old->jenis.' tanggal '.$tanggal.'. Data absensi mapel sudah otomatis disesuaikan.',
                            'kategori' => 'absensi_siswa_diubah',
                            'severity' => 'info',
                            'source_type' => 'student_permit_requests',
                            'source_id' => $id,
                            'payload' => [
                                'siswa' => $siswa->nama,
                                'tanggal' => $tanggal,
                                'status' => $old->jenis,
                            ],
                        ]);
                        $notifiedGuru->push($guruId);
                    }
                }
            }
        }

        AuditLogger::record('review', 'student_permit_requests', $id, 'Pengajuan izin/sakit direview', $old, DB::table('student_permit_requests')->where('id', $id)->first(), $request);

        return [
            'harian' => $createdHarian,
            'mapel' => $createdMapel,
            'guru_notified' => $notifiedGuru->count(),
        ];
    }
}

if (! function_exists('mysqlToolPath')) {
    function mysqlToolPath(string $tool): string
    {
        $candidates = [
            'C:\\xampp2\\mysql\\bin\\'.$tool.'.exe',
            'C:\\xampp\\mysql\\bin\\'.$tool.'.exe',
            $tool,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === $tool || is_file($candidate)) {
                return $candidate;
            }
        }

        return $tool;
    }
}

if (! function_exists('mysqlCommandArgs')) {
    function mysqlCommandArgs(string $tool): array
    {
        $connection = config('database.default');
        $config = config('database.connections.'.$connection);
        $host = ($config['host'] ?? '127.0.0.1') === 'localhost' ? '127.0.0.1' : ($config['host'] ?? '127.0.0.1');
        $args = [
            mysqlToolPath($tool),
            '--protocol=TCP',
            '--host='.$host,
            '--port='.($config['port'] ?? 3306),
            '--user='.($config['username'] ?? 'root'),
        ];

        if (! empty($config['password'])) {
            $args[] = '--password='.$config['password'];
        }

        $args[] = $config['database'];

        return $args;
    }
}

if (! function_exists('mysqlCommandArgsWithOptions')) {
    function mysqlCommandArgsWithOptions(string $tool, array $options = []): array
    {
        $args = mysqlCommandArgs($tool);
        array_splice($args, -1, 0, $options);

        return $args;
    }
}

if (! function_exists('sqlValue')) {
    function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return DB::getPdo()->quote((string) $value);
    }
}

if (! function_exists('buatSqlDumpLaravel')) {
    function buatSqlDumpLaravel(): string
    {
        $database = config('database.connections.'.config('database.default').'.database');
        $tables = collect(DB::select('SHOW TABLES'))->map(fn ($row) => array_values((array) $row)[0])->values();
        $lines = [
            '-- Backup SQL Absensi QR',
            '-- Dibuat: '.now()->toDateTimeString(),
            '-- Database: '.$database,
            'SET FOREIGN_KEY_CHECKS=0;',
            'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";',
            'START TRANSACTION;',
            '',
        ];

        foreach ($tables as $table) {
            $create = (array) DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
            $createSql = array_values($create)[1] ?? '';
            $rows = DB::table($table)->get();

            $lines[] = '--';
            $lines[] = '-- Struktur tabel `'.$table.'`';
            $lines[] = '--';
            $lines[] = 'DROP TABLE IF EXISTS `'.$table.'`;';
            $lines[] = $createSql.';';
            $lines[] = '';

            if ($rows->isEmpty()) {
                continue;
            }

            $columns = array_keys((array) $rows->first());
            $columnSql = collect($columns)->map(fn ($column) => '`'.$column.'`')->implode(', ');
            $lines[] = '-- Data tabel `'.$table.'`';

            foreach ($rows->chunk(200) as $chunk) {
                $values = $chunk->map(function ($row) use ($columns) {
                    $row = (array) $row;

                    return '('.collect($columns)->map(fn ($column) => sqlValue($row[$column] ?? null))->implode(', ').')';
                })->implode(",\n");

                $lines[] = 'INSERT INTO `'.$table.'` ('.$columnSql.') VALUES';
                $lines[] = $values.';';
            }

            $lines[] = '';
        }

        $lines[] = 'COMMIT;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines)."\n";
    }
}

if (! function_exists('hariLiburSekolah')) {
    function hariLiburSekolah($tanggal)
    {
        if (! Schema::hasTable('kalender_sekolahs')) {
            return null;
        }

        $hari = strtolower(Carbon::parse($tanggal)->locale('id')->translatedFormat('l'));

        return DB::table('kalender_sekolahs')
            ->where('jenis', 'libur')
            ->where(function ($query) use ($tanggal, $hari) {
                $query->where(function ($date) use ($tanggal) {
                    $date->whereDate('tanggal_mulai', '<=', $tanggal)
                        ->whereDate('tanggal_selesai', '>=', $tanggal);
                })->orWhere(function ($repeat) use ($hari) {
                    $repeat->where('berulang', 1)->where('hari_berulang', $hari);
                });
            })
            ->first();
    }
}

if (! function_exists('kalenderSekolahTanggal')) {
    function kalenderSekolahTanggal($tanggal)
    {
        if (! Schema::hasTable('kalender_sekolahs')) {
            return collect();
        }

        $hari = strtolower(Carbon::parse($tanggal)->locale('id')->translatedFormat('l'));

        return DB::table('kalender_sekolahs')
            ->where(function ($query) use ($tanggal, $hari) {
                $query->where(function ($date) use ($tanggal) {
                    $date->whereDate('tanggal_mulai', '<=', $tanggal)
                        ->whereDate('tanggal_selesai', '>=', $tanggal);
                })->orWhere(function ($repeat) use ($hari) {
                    $repeat->where('berulang', 1)->where('hari_berulang', $hari);
                });
            })
            ->orderByRaw("FIELD(jenis, 'libur', 'ujian', 'kegiatan')")
            ->get();
    }
}

if (! function_exists('infoLiburHariIni')) {
    function infoLiburHariIni(?string $role = null)
    {
        $tanggal = now()->toDateString();
        $hari = strtolower(now()->locale('id')->translatedFormat('l'));
        $items = collect();

        if ($hari === 'minggu') {
            $items->push((object) [
                'sumber' => 'hari_minggu',
                'judul' => 'Hari Minggu',
                'keterangan' => 'Hari ini adalah hari Minggu. Kegiatan absensi rutin sekolah tidak berjalan.',
                'jenis' => 'libur',
            ]);
        }

        foreach (kalenderSekolahTanggal($tanggal)->where('jenis', 'libur') as $event) {
            $items->push((object) [
                'sumber' => 'kalender',
                'judul' => $event->judul,
                'keterangan' => $event->keterangan ?: 'Tanggal ini ditandai sebagai libur pada kalender sekolah.',
                'jenis' => 'libur',
            ]);
        }

        if (Schema::hasTable('announcements')) {
            $targetRoles = collect(['semua']);
            if ($role) {
                $targetRoles->push($role);
                if ($role === 'guru') {
                    $targetRoles->push('wali', 'piket');
                }
            }

            $pengumumanLibur = DB::table('announcements')
                ->where('aktif', 1)
                ->where('kategori', 'libur')
                ->whereNull('deleted_at')
                ->whereIn('target_role', $targetRoles->unique()->values())
                ->where(function ($query) use ($tanggal) {
                    $query->where(function ($rentang) use ($tanggal) {
                        $rentang->whereDate('tanggal_mulai', '<=', $tanggal)
                            ->whereDate('tanggal_selesai', '>=', $tanggal);
                    })->orWhere(function ($tanpaTanggal) {
                        $tanpaTanggal->whereNull('tanggal_mulai')
                            ->whereNull('tanggal_selesai');
                    });
                })
                ->latest('id')
                ->get();

            foreach ($pengumumanLibur as $item) {
                $items->push((object) [
                    'sumber' => 'pengumuman',
                    'judul' => $item->judul,
                    'keterangan' => $item->isi,
                    'jenis' => 'libur',
                ]);
            }
        }

        return $items->unique(fn ($item) => $item->sumber.'-'.$item->judul)->values();
    }
}

if (! function_exists('buatNotifikasiKalenderBesok')) {
    function buatNotifikasiKalenderBesok(): void
    {
        if (! Schema::hasTable('kalender_sekolahs') || ! Schema::hasTable('notifications')) {
            return;
        }

        $besok = now()->addDay()->toDateString();
        foreach (kalenderSekolahTanggal($besok) as $event) {
            $pesan = 'Besok ada '.ucfirst($event->jenis).': '.$event->judul.' ('.$besok.').';
            $exists = DB::table('notifications')
                ->where('judul', 'Pengingat Kalender Sekolah')
                ->where('pesan', $pesan)
                ->exists();

            if (! $exists) {
                buatNotifikasi([
                    'user_id' => null,
                    'judul' => 'Pengingat Kalender Sekolah',
                    'pesan' => $pesan,
                    'kategori' => 'kalender',
                    'severity' => $event->jenis === 'libur' ? 'danger' : 'info',
                    'source_type' => 'kalender_sekolahs',
                    'source_id' => $event->id ?? null,
                    'payload' => [
                        'jenis' => $event->jenis,
                        'tanggal' => $besok,
                        'judul_event' => $event->judul,
                    ],
                    'status' => 'belum_dibaca',
                ]);
            }
        }
    }
}

if (! function_exists('daftarProvinsiIndonesia')) {
    function daftarProvinsiIndonesia(): array
    {
        return [
            'Nasional', 'Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau',
            'Jambi', 'Bengkulu', 'Sumatera Selatan', 'Bangka Belitung', 'Lampung', 'Banten',
            'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Jawa Timur', 'Bali',
            'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat', 'Kalimantan Tengah',
            'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara', 'Sulawesi Utara',
            'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan',
            'Sulawesi Tenggara', 'Maluku', 'Maluku Utara', 'Papua', 'Papua Barat',
        ];
    }
}

if (! function_exists('validasiBentrokJadwalPelajaran')) {
    function validasiBentrokJadwalPelajaran(Request $request, ?int $ignoreId = null): ?string
    {
        if ($request->jam_mulai >= $request->jam_selesai) {
            return 'Jam selesai harus lebih besar dari jam mulai.';
        }

        $hari = strtolower($request->hari);
        $tahunAjaranId = $request->tahun_ajaran_id ?: tahunAjaranAktifId();

        $kelasBentrok = DB::table('jadwal_pelajarans')
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where('kelas_id', $request->kelas_id);

        if ($bentrok = jamBentrok($kelasBentrok, $request->jam_mulai, $request->jam_selesai)->first()) {
            $kelas = DB::table('kelas')->where('id', $request->kelas_id)->value('nama_kelas') ?: 'Kelas';

            return $kelas.' sudah memiliki jadwal pelajaran pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        $guruIds = array_values(array_filter([
            $request->guru_id,
            $request->guru_pengganti_id,
        ]));

        $guruBentrok = DB::table('jadwal_pelajarans')
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where(function ($query) use ($guruIds) {
                $query->whereIn('guru_id', $guruIds)
                    ->orWhereIn('guru_pengganti_id', $guruIds);
            });

        if ($guruIds && ($bentrok = jamBentrok($guruBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            $namaGuru = User::whereIn('id', $guruIds)->where(function ($query) use ($bentrok) {
                $query->where('id', $bentrok->guru_id)->orWhere('id', $bentrok->guru_pengganti_id);
            })->value('nama') ?: 'Guru tersebut';

            return $namaGuru.' sudah memiliki jadwal mengajar pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        $guruPiketBentrok = DB::table('guru_pikets')
            ->when($tahunAjaranId && Schema::hasColumn('guru_pikets', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where('aktif', 1)
            ->where(function ($query) use ($guruIds) {
                $query->whereIn('guru_id', $guruIds)
                    ->orWhereIn('guru_pengganti_id', $guruIds)
                    ->orWhereIn('guru_pengganti2_id', $guruIds);
            });

        if ($guruIds && ($bentrok = jamBentrok($guruPiketBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            $namaGuru = User::whereIn('id', $guruIds)->where(function ($query) use ($bentrok) {
                $query->where('id', $bentrok->guru_id)
                    ->orWhere('id', $bentrok->guru_pengganti_id)
                    ->orWhere('id', $bentrok->guru_pengganti2_id);
            })->value('nama') ?: 'Guru tersebut';

            return $namaGuru.' sedang bertugas sebagai guru piket pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        return null;
    }
}

if (! function_exists('validasiJadwalSaatLibur')) {
    function validasiJadwalSaatLibur(Request $request): ?string
    {
        if (! Schema::hasTable('kalender_sekolahs')) {
            return null;
        }

        $tahunAjaranId = $request->tahun_ajaran_id ?: tahunAjaranAktifId();
        $hari = strtolower($request->hari);
        $hariIni = strtolower(now()->locale('id')->translatedFormat('l'));
        $liburHariIni = hariLiburSekolah(now()->toDateString());

        if ($liburHariIni && $hari === $hariIni) {
            return 'Jadwal ditolak. Hari ini libur: '.$liburHariIni->judul.'. Tidak bisa input mapel atau membuat jadwal pada hari libur.';
        }

        $berulang = DB::table('kalender_sekolahs')
            ->where('jenis', 'libur')
            ->where('berulang', 1)
            ->where('hari_berulang', $hari)
            ->where(function ($query) use ($tahunAjaranId) {
                $query->where('tahun_ajaran_id', $tahunAjaranId)->orWhereNull('tahun_ajaran_id');
            })
            ->first();

        if ($berulang) {
            return 'Tidak bisa membuat jadwal mapel pada hari '.ucfirst($hari).', karena ditandai libur: '.$berulang->judul.'.';
        }

        return null;
    }
}

if (! function_exists('validasiBentrokGuruPiket')) {
    function validasiBentrokGuruPiket(Request $request, ?int $ignoreId = null): ?string
    {
        if ($request->jam_mulai >= $request->jam_selesai) {
            return 'Jam selesai harus lebih besar dari jam mulai.';
        }

        $hari = strtolower($request->hari);
        $tahunAjaranId = $request->tahun_ajaran_id ?: tahunAjaranAktifId();
        $guruIds = array_values(array_unique(array_filter(array_merge(
            (array) $request->guru_id,
            [
                $request->guru_pengganti_id,
                $request->guru_pengganti2_id,
            ]
        ))));

        $piketBentrok = DB::table('guru_pikets')
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->when($tahunAjaranId && Schema::hasColumn('guru_pikets', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where('aktif', 1)
            ->where(function ($query) use ($guruIds) {
                $query->whereIn('guru_id', $guruIds)
                    ->orWhereIn('guru_pengganti_id', $guruIds)
                    ->orWhereIn('guru_pengganti2_id', $guruIds);
            });

        if ($guruIds && ($bentrok = jamBentrok($piketBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            return 'Ada guru yang sudah memiliki jadwal piket pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        $jadwalBentrok = DB::table('jadwal_pelajarans')
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where(function ($query) use ($guruIds) {
                $query->whereIn('guru_id', $guruIds)
                    ->orWhereIn('guru_pengganti_id', $guruIds);
            });

        if ($guruIds && ($bentrok = jamBentrok($jadwalBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            return 'Ada guru yang sudah memiliki jadwal mengajar pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.', jadi tidak bisa dijadikan guru piket.';
        }

        return null;
    }
}

if (! function_exists('detailProfilSiswaData')) {
    function detailProfilSiswaData(int $siswaId): array
    {
        $siswa = DB::table('users as s')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
            ->leftJoin('jurusan as j', 'j.id', '=', 'k.jurusan_id')
            ->leftJoin('users as w', 'w.id', '=', 'k.wali_kelas_id')
            ->where('s.id', $siswaId)
            ->where('s.role', 'siswa')
            ->whereNull('s.deleted_at')
            ->select('s.*', 'k.nama_kelas', 'j.kode_jurusan', 'w.nama as nama_wali')
            ->first();

        abort_if(! $siswa, 404);

        $absensiHarian = DB::table('absensis')
            ->where('id_siswa', $siswaId)
            ->whereNull('deleted_at')
            ->orderByDesc('tanggal')
            ->limit(60)
            ->get();

        $absensiMapel = DB::table('absensi_mapels as a')
            ->join('jadwal_pelajarans as jp', 'jp.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->where('a.siswa_id', $siswaId)
            ->whereNull('a.deleted_at')
            ->select('a.*', 'm.nama_mapel', 'g.nama as nama_guru')
            ->orderByDesc('a.tanggal')
            ->limit(80)
            ->get();

        $defaultAlfa = AttendanceSettingService::statusDefaultAlfa();

        $ringkasanHarian = [
            'hadir' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
            $defaultAlfa => 0,
        ];
        $riwayatKhusus = collect();

        foreach ($absensiHarian as $row) {
            $status = in_array($row->status_masuk, ['izin', 'sakit', 'alfa', 'alpa'])
                ? $row->status_masuk
                : (in_array($row->status_pulang, ['izin', 'sakit', 'alfa', 'alpa']) ? $row->status_pulang : null);

            if (! $status) {
                $status = in_array($row->status_masuk, ['telat', 'terlambat']) ? 'telat' : 'hadir';
            }

            $key = in_array($status, ['alfa', 'alpa']) ? $defaultAlfa : $status;
            $ringkasanHarian[$key] = ($ringkasanHarian[$key] ?? 0) + 1;

            if (in_array($key, ['izin', 'sakit', 'alfa', 'alpa'])) {
                $riwayatKhusus->push([
                    'tanggal' => $row->tanggal,
                    'sumber' => 'Absensi Harian',
                    'status' => $key,
                    'keterangan' => 'Masuk: '.($row->status_masuk ?? '-').' | Pulang: '.($row->status_pulang ?? '-'),
                ]);
            }
        }

        $ringkasanMapel = [
            'hadir' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
            $defaultAlfa => 0,
        ];

        foreach ($absensiMapel as $row) {
            $key = strtolower($row->status ?? 'hadir');
            $key = in_array($key, ['alfa', 'alpa']) ? $defaultAlfa : $key;
            $ringkasanMapel[$key] = ($ringkasanMapel[$key] ?? 0) + 1;

            if (in_array($key, ['izin', 'sakit', 'alfa', 'alpa'])) {
                $riwayatKhusus->push([
                    'tanggal' => $row->tanggal,
                    'sumber' => 'Absensi Mapel',
                    'status' => $key,
                    'keterangan' => ($row->nama_mapel ?? '-').' | Guru: '.($row->nama_guru ?? '-'),
                ]);
            }
        }

        return [
            'siswa' => $siswa,
            'absensiHarian' => $absensiHarian,
            'absensiMapel' => $absensiMapel,
            'ringkasanHarian' => $ringkasanHarian,
            'ringkasanMapel' => $ringkasanMapel,
            'riwayatKhusus' => $riwayatKhusus->sortByDesc('tanggal')->values(),
        ];
    }
}
