<?php

use App\Http\Controllers\Admin\AdminFeatureController;
use App\Http\Controllers\Admin\AbsensiAdminController;
use App\Http\Controllers\Admin\GuruController;
use App\Http\Controllers\Admin\GuruPiketController;
use App\Http\Controllers\Admin\JadwalController;
use App\Http\Controllers\Admin\JurusanController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\KalenderSekolahController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ArsipController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\KeamananController;
use App\Http\Controllers\Admin\KesehatanDataController;
use App\Http\Controllers\Admin\NotifikasiSettingController;
use App\Http\Controllers\Admin\PengajuanIzinController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PengumumanController as AdminPengumumanController;
use App\Http\Controllers\Admin\AutoAlfaController;
use App\Http\Controllers\Admin\AdminPdfController;
use App\Http\Controllers\Admin\RekapAdminController;
use App\Http\Controllers\Admin\RoleAksesController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TahunAjaranController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WaliKelasController as AdminWaliKelasController;
use App\Http\Controllers\Absensi\AbsensiNavigasiController;
use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\BantuanController;
use App\Http\Controllers\Web\HomeRedirectController;
use App\Http\Controllers\Web\NotifikasiSayaController;
use App\Http\Controllers\Web\PengumumanController;
use App\Http\Controllers\Web\RiwayatPerubahanController;
use App\Http\Controllers\Web\HeartbeatController;
use App\Http\Controllers\Web\RoleCommunicationController;
use App\Http\Controllers\Web\ManualAbsensiController;
use App\Http\Controllers\Admin\AdminUtilityController;
use App\Http\Controllers\Dashboard\GuruDashboardController;
use App\Http\Controllers\Dashboard\GuruActionController;
use App\Http\Controllers\Dashboard\PiketDashboardController;
use App\Http\Controllers\Dashboard\RoleReportController;
use App\Http\Controllers\Dashboard\SiswaDashboardController;
use App\Http\Controllers\Dashboard\WaliKelasDashboardController;
use App\Http\Controllers\Qr\QrViewController;
use App\Models\User;
use App\Services\AttendanceSettingService;
use App\Support\AbsensiRekapSync;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

Route::get('/', [HomeRedirectController::class, 'index']);
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
        $siswaQuery = DB::table('users')->where('role', 'siswa')->where('aktif', 1);
        if ($kelasId) {
            $siswaQuery->where('kelas_id', $kelasId);
        }
        $siswaIds = $siswaQuery->pluck('id');

        $belumPulang = DB::table('absensis as a')
            ->join('users as s', 's.id', '=', 'a.id_siswa')
            ->leftJoin('kelas as k', 'k.id', '=', 's.kelas_id')
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
                $join->on('am.jadwal_id', '=', 'j.id')->on('am.siswa_id', '=', 's.id')->whereBetween('am.tanggal', [$mulai, $selesai]);
            })
            ->leftJoin('kelas as k', 'k.id', '=', 'j.kelas_id')
            ->leftJoin('mapels as m', 'm.id', '=', 'j.mapel_id')
            ->whereIn('s.id', $siswaIds)
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
        $siswa = User::where('role', 'siswa')->where('aktif', 1)->get();

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
            $siswa = User::where('role', 'siswa')->findOrFail($old->siswa_id);
            $period = CarbonPeriod::create($old->tanggal_mulai, $old->tanggal_selesai);

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
            ->select('s.*', 'k.nama_kelas', 'j.kode_jurusan', 'w.nama as nama_wali')
            ->first();

        abort_if(! $siswa, 404);

        $absensiHarian = DB::table('absensis')
            ->where('id_siswa', $siswaId)
            ->orderByDesc('tanggal')
            ->limit(60)
            ->get();

        $absensiMapel = DB::table('absensi_mapels as a')
            ->join('jadwal_pelajarans as jp', 'jp.id', '=', 'a.jadwal_id')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->join('users as g', 'g.id', '=', 'jp.guru_id')
            ->where('a.siswa_id', $siswaId)
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
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login']);
Route::get('/logout', [AuthWebController::class, 'logout']);
Route::get('/heartbeat', HeartbeatController::class);

Route::get('/dashboard/pengumuman', [PengumumanController::class, 'public'])->middleware('webrole:guru,piket');
Route::get('/dashboard/bantuan', [BantuanController::class, 'dashboard'])->middleware('webrole:admin,guru,piket,siswa');
Route::get('/bantuan', [BantuanController::class, 'public']);
Route::get('/dashboard/notifikasi-saya', [NotifikasiSayaController::class, 'index'])->middleware('webrole:guru,piket,siswa');
Route::get('/dashboard/riwayat-perubahan-saya', [RiwayatPerubahanController::class, 'index'])->middleware('webrole:guru,piket');

Route::get('/dashboard/pesan-internal', [RoleCommunicationController::class, 'pesanIndex'])->middleware('webrole:guru,piket');
Route::post('/dashboard/pesan-internal', [RoleCommunicationController::class, 'pesanStore'])->middleware('webrole:guru,piket');
Route::get('/dashboard/delegasi-sementara', [RoleCommunicationController::class, 'delegasiIndex'])->middleware('webrole:guru,piket');
Route::post('/dashboard/delegasi-sementara', [RoleCommunicationController::class, 'delegasiStore'])->middleware('webrole:guru,piket');

Route::get(

    '/dashboard/admin',

    function () {

        $user =
        session(
            'user'
        );

        $totalSiswa =

        User::where(

            'role',

            'siswa'

        )
            ->count();

        $totalGuru =

        User::where(

            'role',

            'guru'

        )
            ->count();

        $totalKelas =

        DB::table(

            'kelas'

        )
            ->count();

        $totalJurusan =

        DB::table(

            'jurusan'

        )
            ->count();

        $totalNotifikasi = DB::table('notifications')
            ->whereNull('user_id')
            ->where('status', 'belum_dibaca')
            ->count();
        buatNotifikasiKalenderBesok();
        buatNotifikasiBulanBelumDitutup();

        $tahunAjaranAktif = DB::table('tahun_ajarans')
            ->where('aktif', true)
            ->first();
        $kalenderHariIni = kalenderSekolahTanggal(now()->toDateString());
        $infoLiburHariIni = infoLiburHariIni('admin');

        $absensiHariIni = DB::table('absensis')
            ->whereDate('tanggal', now()->toDateString());

        $totalMasukHariIni = (clone $absensiHariIni)
            ->whereNotNull('jam_masuk')
            ->count();

        $totalPulangHariIni = (clone $absensiHariIni)
            ->whereNotNull('jam_pulang')
            ->count();

        $totalBelumAbsen = max($totalSiswa - $totalMasukHariIni, 0);

        $siswaPerKelas = DB::table('kelas as k')
            ->leftJoin('users as s', function ($join) {
                $join->on('s.kelas_id', '=', 'k.id')
                    ->where('s.role', 'siswa');
            })
            ->select('k.nama_kelas', DB::raw('COUNT(s.id) as total'))
            ->groupBy('k.id', 'k.nama_kelas')
            ->orderBy('k.nama_kelas')
            ->get();

        $guruPiketAktif = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->count();

        $guruPiketTidakHadir = DB::table('guru_pikets')
            ->where('hari', strtolower(now()->locale('id')->translatedFormat('l')))
            ->where('aktif', 1)
            ->whereIn('status', ['Izin', 'Sakit'])
            ->count();

        $chartData = [
            'absensi' => [
                'labels' => ['Masuk', 'Pulang', 'Belum Absen'],
                'values' => [$totalMasukHariIni, $totalPulangHariIni, $totalBelumAbsen],
            ],
            'kelas' => [
                'labels' => $siswaPerKelas->pluck('nama_kelas')->values(),
                'values' => $siswaPerKelas->pluck('total')->values(),
            ],
            'piket' => [
                'labels' => ['Bertugas', 'Tidak Hadir'],
                'values' => [$guruPiketAktif, $guruPiketTidakHadir],
            ],
        ];

        return view(

            'dashboard.admin',

            compact(

                'user',

                'totalSiswa',

                'totalGuru',

                'totalKelas',

                'totalJurusan',

                'totalNotifikasi',
                'tahunAjaranAktif',
                'kalenderHariIni',
                'infoLiburHariIni',

                'totalMasukHariIni',

                'totalPulangHariIni',

                'totalBelumAbsen',

                'guruPiketAktif',

                'guruPiketTidakHadir',

                'chartData'

            )

        );

    })
    ->middleware(

        'webrole:admin'

    )
    ->name('dashboard.admin');

Route::get('/dashboard/admin/online-users', [AdminUtilityController::class, 'onlineUsers'])->middleware('webrole:admin');
Route::post('/dashboard/admin/bulk-delete', [AdminUtilityController::class, 'bulkDelete'])->middleware('webrole:admin');
Route::get('/dashboard/admin/notifikasi', [AdminUtilityController::class, 'notifikasi'])->middleware('webrole:admin');

Route::middleware('webrole:admin')->group(function () {
    Route::get('/dashboard/admin/kalender-sekolah', [KalenderSekolahController::class, 'index']);

    Route::get('/dashboard/admin/kalender-sekolah/create', [KalenderSekolahController::class, 'create']);

    Route::post('/dashboard/admin/kalender-sekolah/store', [KalenderSekolahController::class, 'store']);

    Route::get('/dashboard/admin/kalender-sekolah/edit/{id}', [KalenderSekolahController::class, 'edit']);

    Route::post('/dashboard/admin/kalender-sekolah/update/{id}', [KalenderSekolahController::class, 'update']);

    Route::get('/dashboard/admin/kalender-sekolah/delete/{id}', [KalenderSekolahController::class, 'delete']);

    Route::post('/dashboard/admin/kalender-sekolah/auto-nasional', [KalenderSekolahController::class, 'autoNasional']);

    Route::get('/dashboard/admin/pengaturan', [PengaturanController::class, 'index']);

    Route::post('/dashboard/admin/pengaturan', [PengaturanController::class, 'store']);

    Route::get('/dashboard/admin/audit-log', [AuditLogController::class, 'index']);

    Route::get('/dashboard/admin/audit-log/{id}', [AuditLogController::class, 'show'])->whereNumber('id');

    Route::get('/dashboard/admin/backup', [BackupController::class, 'index']);

    Route::post('/dashboard/admin/backup/create', [BackupController::class, 'create']);

    Route::post('/dashboard/admin/backup/create-sql', [BackupController::class, 'createSql']);

    Route::get('/dashboard/admin/backup/download/{file}', [BackupController::class, 'download']);

    Route::post('/dashboard/admin/backup/restore/{file}', [BackupController::class, 'restore']);

    Route::get('/dashboard/admin/arsip', [ArsipController::class, 'index']);

    Route::get('/dashboard/admin/arsip/preview', [ArsipController::class, 'preview']);

    Route::post('/dashboard/admin/arsip/restore', [ArsipController::class, 'restore']);

    Route::post('/dashboard/admin/arsip/bulk-restore', [ArsipController::class, 'bulkRestore']);

    Route::post('/dashboard/admin/arsip/force-delete', [ArsipController::class, 'forceDelete']);

    Route::post('/dashboard/admin/arsip/bulk-force-delete', [ArsipController::class, 'bulkForceDelete']);

    Route::get('/dashboard/admin/keamanan', [KeamananController::class, 'index']);

    Route::get('/dashboard/admin/kesehatan-data', [KesehatanDataController::class, 'index']);

    Route::get('/dashboard/admin/role-akses', [RoleAksesController::class, 'index']);

    Route::get('/dashboard/admin/notifikasi-setting', [NotifikasiSettingController::class, 'index']);

    Route::post('/dashboard/admin/notifikasi-setting', [NotifikasiSettingController::class, 'store']);

    Route::get('/dashboard/admin/pengumuman', [AdminPengumumanController::class, 'index']);

    Route::get('/dashboard/admin/pengumuman/create', [AdminPengumumanController::class, 'create']);

    Route::post('/dashboard/admin/pengumuman/store', [AdminPengumumanController::class, 'store']);

    Route::get('/dashboard/admin/pengumuman/edit/{id}', [AdminPengumumanController::class, 'edit'])->whereNumber('id');

    Route::post('/dashboard/admin/pengumuman/update/{id}', [AdminPengumumanController::class, 'update'])->whereNumber('id');

    Route::get('/dashboard/admin/pengumuman/delete/{id}', [AdminPengumumanController::class, 'delete'])->whereNumber('id');

    Route::get('/dashboard/admin/pengajuan-izin', [PengajuanIzinController::class, 'index']);

    Route::post('/dashboard/admin/pengajuan-izin/{id}/review', [PengajuanIzinController::class, 'review'])->whereNumber('id');

    Route::post('/dashboard/admin/auto-alfa', [AutoAlfaController::class, 'store']);

    Route::get('/dashboard/admin/rekap/absensi-pdf', [AdminPdfController::class, 'absensiHarian']);

    Route::get('/dashboard/admin/rekap/absensi-mapel-pdf', [AdminPdfController::class, 'absensiMapel']);

    Route::get('/dashboard/admin/rekap/guru-piket-pdf', [AdminPdfController::class, 'guruPiket']);

    Route::get('/dashboard/admin/rekap/jadwal-digantikan-pdf', [AdminPdfController::class, 'jadwalDigantikan']);

    Route::get('/dashboard/admin/rekap/jadwal-guru-mapel-pdf', [AdminPdfController::class, 'jadwalGuruMapel']);

    Route::get('/dashboard/admin/rekap/wali-kelas-pdf', [AdminPdfController::class, 'waliKelas']);

    Route::get('/dashboard/admin/pdf/{type}', [AdminPdfController::class, 'admin']);

    Route::get('/dashboard/admin/siswa/detail/{id}/pdf', [AdminPdfController::class, 'detailSiswa'])->whereNumber('id');

    Route::get('/dashboard/admin/tahun-ajaran', [TahunAjaranController::class, 'index']);

    Route::get('/dashboard/admin/tahun-ajaran/create', [TahunAjaranController::class, 'create']);

    Route::post('/dashboard/admin/tahun-ajaran/store', [TahunAjaranController::class, 'store']);

    Route::get('/dashboard/admin/tahun-ajaran/edit/{id}', [TahunAjaranController::class, 'edit']);

    Route::post('/dashboard/admin/tahun-ajaran/update/{id}', [TahunAjaranController::class, 'update']);

    Route::post('/dashboard/admin/tahun-ajaran/{id}/aktif', [TahunAjaranController::class, 'aktif']);

    Route::get('/dashboard/admin/tahun-ajaran/delete/{id}', [TahunAjaranController::class, 'delete']);

    Route::get('/dashboard/admin/rekap/guru-piket', [RekapAdminController::class, 'guruPiket']);

    Route::get('/dashboard/admin/rekap/jadwal-digantikan', [RekapAdminController::class, 'jadwalDigantikan']);

    Route::get('/dashboard/admin/rekap/absensi-mapel', [RekapAdminController::class, 'absensiMapel']);

    Route::get('/dashboard/admin/absensi', [AbsensiAdminController::class, 'index']);

    Route::get('/dashboard/admin/absensi/create', [AbsensiAdminController::class, 'create']);

    Route::post('/dashboard/admin/absensi/sinkron-rekap', [AbsensiAdminController::class, 'sinkronRekap']);

    Route::post('/dashboard/admin/absensi/store', [AbsensiAdminController::class, 'store']);

    Route::get('/dashboard/admin/absensi/{id}', [AbsensiAdminController::class, 'show'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi/edit/{id}', [AbsensiAdminController::class, 'edit'])->whereNumber('id');

    Route::post('/dashboard/admin/absensi/update/{id}', [AbsensiAdminController::class, 'update'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi/delete/{id}', [AbsensiAdminController::class, 'delete'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel', [AbsensiAdminController::class, 'mapelIndex']);

    Route::get('/dashboard/admin/absensi-mapel/create', [AbsensiAdminController::class, 'mapelCreate']);

    Route::post('/dashboard/admin/absensi-mapel/store', [AbsensiAdminController::class, 'mapelStore']);

    Route::get('/dashboard/admin/absensi-mapel/{id}', [AbsensiAdminController::class, 'mapelShow'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel/edit/{id}', [AbsensiAdminController::class, 'mapelEdit'])->whereNumber('id');

    Route::post('/dashboard/admin/absensi-mapel/update/{id}', [AbsensiAdminController::class, 'mapelUpdate'])->whereNumber('id');

    Route::get('/dashboard/admin/absensi-mapel/delete/{id}', [AbsensiAdminController::class, 'mapelDelete'])->whereNumber('id');

    Route::get('/dashboard/admin/rekap/jadwal-guru-mapel', [RekapAdminController::class, 'jadwalGuruMapel']);
});

/*
|--------------------------------------------------------------------------
| FITUR TAMBAHAN ADMIN
|--------------------------------------------------------------------------
*/

Route::middleware('webrole:admin')->group(function () {

    Route::get('/dashboard/admin/users', [UserController::class, 'index']);

    Route::get('/dashboard/admin/users/create', [UserController::class, 'create'])->whereNumber('id');

    Route::post('/dashboard/admin/users/store', [UserController::class, 'store']);

    Route::get('/dashboard/admin/users/edit/{id}', [UserController::class, 'edit']);

    Route::post('/dashboard/admin/users/update/{id}', [UserController::class, 'update'])->whereNumber('id');

    Route::get('/dashboard/admin/users/delete/{id}', [UserController::class, 'delete'])->whereNumber('id');

    Route::get(
        '/dashboard/admin/siswa/import',
        [AdminFeatureController::class, 'importSiswaForm']
    );

    Route::post(
        '/dashboard/admin/siswa/import',
        [AdminFeatureController::class, 'importSiswa']
    );

    Route::get(
        '/dashboard/admin/siswa/template',
        [AdminFeatureController::class, 'downloadTemplateSiswa',
        ]);

    Route::get(
        '/dashboard/admin/jadwal/import',
        [AdminFeatureController::class, 'importJadwalForm']
    );

    Route::post(
        '/dashboard/admin/jadwal/import',
        [AdminFeatureController::class, 'importJadwal']
    );

    Route::get(
        '/dashboard/admin/jadwal/template',
        [AdminFeatureController::class, 'downloadTemplateJadwal']
    );

    Route::get(
        '/dashboard/admin/kalender-sekolah/template',
        [AdminFeatureController::class, 'downloadTemplateKalender']
    );

    Route::post(
        '/dashboard/admin/kalender-sekolah/import',
        [AdminFeatureController::class, 'importKalender']
    );

    Route::get(
        '/dashboard/admin/kalender-sekolah/export',
        [AdminFeatureController::class, 'exportKalender']
    );

    /*
    |--------------------------------------------------------------------------
    | REKAP ABSENSI
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/absensi/rekap',
        [AdminFeatureController::class, 'rekapAbsensi']
    )
        ->name(
            'rekap.absensi'
        );

    /*
    |--------------------------------------------------------------------------
    | EXPORT EXCEL
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/absensi/export',
        [AdminFeatureController::class, 'exportAbsensi']
    )
        ->name(
            'export.absensi'
        );

    /*
    |--------------------------------------------------------------------------



    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD USER
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/users/{id}/reset-password',
        [AdminFeatureController::class, 'resetPasswordForm']
    );

    Route::post(
        '/dashboard/admin/users/{id}/reset-password',
        [AdminFeatureController::class, 'resetPassword']
    );

    Route::post(
        '/dashboard/admin/users/{id}/toggle-active',
        [AdminFeatureController::class, 'toggleActive']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT GURU
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/guru/edit/{id}',
        [AdminFeatureController::class, 'editGuru']
    );

    Route::post(
        '/dashboard/admin/guru/update/{id}',
        [AdminFeatureController::class, 'updateGuru']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT KELAS
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/kelas/edit/{id}',
        [AdminFeatureController::class, 'editKelas']
    );

    Route::post(
        '/dashboard/admin/kelas/update/{id}',
        [AdminFeatureController::class, 'updateKelas']
    );

    /*
    |--------------------------------------------------------------------------
    | EDIT JURUSAN
    |--------------------------------------------------------------------------
    */
    Route::get(
        '/dashboard/admin/jurusan/edit/{id}',
        [AdminFeatureController::class, 'editJurusan']
    );

    Route::post(
        '/dashboard/admin/jurusan/update/{id}',
        [AdminFeatureController::class, 'updateJurusan']
    );

});

/*
|--------------------------------------------------------------------------
| LIST GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru', [GuruController::class, 'index'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM TAMBAH GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru/create', [GuruController::class, 'create'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN GURU
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/guru/store', [GuruController::class, 'store'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS GURU
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/guru/delete/{id}', [GuruController::class, 'delete'])->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| LIST KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas', [KelasController::class, 'index'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM TAMBAH KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas/create', [KelasController::class, 'create'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN KELAS
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/kelas/store', [KelasController::class, 'store'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/kelas/delete/{id}', [KelasController::class, 'delete'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| LIST JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal', [JadwalController::class, 'index'])->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
| FORM TAMBAH JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal/create', [JadwalController::class, 'create'])->middleware('webrole:admin');

Route::get('/dashboard/admin/jadwal/bentrok', [JadwalController::class, 'bentrok'])->middleware('webrole:admin');

Route::get('/dashboard/admin/jadwal/edit/{id}', [JadwalController::class, 'edit'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
|/*
|--------------------------------------------------------------------------
| SIMPAN JADWAL
|--------------------------------------------------------------------------
*/

Route::post('/dashboard/admin/jadwal/store', [JadwalController::class, 'store'])->middleware('webrole:admin');

Route::post('/dashboard/admin/jadwal/update/{id}', [JadwalController::class, 'update'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS JADWAL
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/jadwal/delete/{id}', [JadwalController::class, 'delete'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| LIST SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa', [SiswaController::class, 'index'])->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| FORM TAMBAH SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/create', [SiswaController::class, 'create'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| SIMPAN SISWA
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/siswa/store', [SiswaController::class, 'store'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM EDIT SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/edit/{id}', [SiswaController::class, 'edit'])->middleware('webrole:admin');

Route::get('/dashboard/admin/siswa/detail/{id}', [SiswaController::class, 'detail'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| UPDATE SISWA
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/siswa/update/{id}', [SiswaController::class, 'update'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS SISWA
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/siswa/delete/{id}', [SiswaController::class, 'delete'])->middleware('webrole:admin');
/*

/*
|--------------------------------------------------------------------------
| LIST WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas', [AdminWaliKelasController::class, 'index'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
/*
|--------------------------------------------------------------------------
| FORM TAMBAH / SET WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/create', [AdminWaliKelasController::class, 'create'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| SIMPAN WALI KELAS
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/wali-kelas/store', [AdminWaliKelasController::class, 'store'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| FORM EDIT WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/edit/{id}', [AdminWaliKelasController::class, 'edit'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| UPDATE WALI KELAS
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/admin/wali-kelas/update/{id}', [AdminWaliKelasController::class, 'update'])->middleware('webrole:admin');

/*
|--------------------------------------------------------------------------
| HAPUS WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/admin/wali-kelas/delete/{id}', [AdminWaliKelasController::class, 'delete'])->middleware('webrole:admin');
/*
|--------------------------------------------------------------------------
| DASHBOARD PIKET
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/piket', [PiketDashboardController::class, 'index'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/absensi-harian', [AbsensiNavigasiController::class, 'piketAbsensiHarian'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/riwayat-absensi', [AbsensiNavigasiController::class, 'piketRiwayatAbsensi'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/rekap-jadwal', [GuruDashboardController::class, 'piketRekapJadwal'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/qr-harian', [QrViewController::class, 'piketQrHarian'])->middleware('webrole:piket,guru');

Route::get('/dashboard/piket/qr/{id}/view', [QrViewController::class, 'piketView'])->middleware('webrole:piket,guru')->whereNumber('id');

Route::post('/dashboard/piket/finalisasi-harian', [PiketDashboardController::class, 'finalisasiHarian'])->middleware('webrole:piket,guru');
Route::get('/dashboard/piket/pengajuan-izin', [PiketDashboardController::class, 'pengajuanIzin'])->middleware('webrole:piket,guru');
Route::post('/dashboard/piket/pengajuan-izin/{id}/review', [PiketDashboardController::class, 'reviewPengajuanIzin'])->middleware('webrole:piket,guru')->whereNumber('id');
Route::get('/dashboard/piket/absensi/{siswaId}/view', [PiketDashboardController::class, 'viewAbsensi'])->middleware('webrole:piket,guru');
Route::get('/dashboard/piket/absensi/{siswaId}/edit', [PiketDashboardController::class, 'editAbsensi'])->middleware('webrole:piket,guru');
Route::post('/dashboard/piket/absensi/{siswaId}/update', [PiketDashboardController::class, 'updateAbsensi'])->middleware('webrole:piket,guru');

/*
|--------------------------------------------------------------------------
| GENERATE QR
|--------------------------------------------------------------------------
*/
Route::post('/dashboard/piket/generate-qr', [PiketDashboardController::class, 'generateQr'])->middleware('webrole:piket,guru');
Route::post('/dashboard/piket/status', [PiketDashboardController::class, 'status'])->middleware('webrole:piket,guru');

/*
|--------------------------------------------------------------------------
| DASHBOARD WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali', [WaliKelasDashboardController::class, 'index'])->middleware('webrole:guru');
/*
|--------------------------------------------------------------------------
| DATA SISWA WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali/siswa', [WaliKelasDashboardController::class, 'siswa'])->middleware('webrole:guru');

Route::get('/dashboard/wali/siswa/detail/{id}', [WaliKelasDashboardController::class, 'detailSiswa'])->middleware('webrole:guru');

/*
|--------------------------------------------------------------------------
| ABSENSI SISWA WALI KELAS
|--------------------------------------------------------------------------
*/
Route::get('/dashboard/wali/absensi', [WaliKelasDashboardController::class, 'absensi'])->middleware('webrole:guru');

Route::post('/dashboard/wali/siswa/{id}/catatan', [WaliKelasDashboardController::class, 'simpanCatatan'])->middleware('webrole:guru')->whereNumber('id');

Route::get('/dashboard/guru/pdf/{type}', [RoleReportController::class, 'guruPdf'])->middleware('webrole:guru');
Route::get('/dashboard/piket/pdf/{type}', [RoleReportController::class, 'piketPdf'])->middleware('webrole:piket,guru');
Route::get('/dashboard/wali/pdf/{type}', [RoleReportController::class, 'waliPdf'])->middleware('webrole:guru');
Route::get('/dashboard/wali/surat/{siswaId}', [RoleReportController::class, 'waliSurat'])->middleware('webrole:guru')->whereNumber('siswaId');
Route::get('/dashboard/guru/laporan-bulanan', [RoleReportController::class, 'guruLaporanBulanan'])->middleware('webrole:guru');
Route::get('/dashboard/piket/laporan-bulanan', [RoleReportController::class, 'piketLaporanBulanan'])->middleware('webrole:piket,guru');
Route::get('/dashboard/wali/laporan-bulanan', [RoleReportController::class, 'waliLaporanBulanan'])->middleware('webrole:guru');
Route::get('/dashboard/validasi-tutup-bulan', [RoleReportController::class, 'validasiTutupBulan'])->middleware('webrole:admin,guru');
Route::post('/dashboard/validasi-tutup-bulan/kunci', [RoleReportController::class, 'kunciTutupBulan'])->middleware('webrole:admin,guru');
Route::get('/dashboard/guru', [GuruDashboardController::class, 'index'])->middleware('webrole:guru');

Route::get('/dashboard/guru/jadwal', [GuruDashboardController::class, 'jadwal'])->middleware('webrole:guru');

Route::get('/dashboard/guru/verifikasi-absensi', [GuruDashboardController::class, 'verifikasiAbsensi'])->middleware('webrole:guru');

Route::get('/dashboard/guru/riwayat-absensi', [GuruDashboardController::class, 'riwayatAbsensi'])->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-siswa', [GuruDashboardController::class, 'rekapSiswa'])->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-absensi', [GuruDashboardController::class, 'rekapAbsensi'])->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-absensi-mapel', [AbsensiNavigasiController::class, 'guruRekapAbsensiMapel'])->middleware('webrole:guru');

Route::get('/dashboard/guru/sesi-digantikan', [GuruDashboardController::class, 'sesiDigantikan'])->middleware('webrole:guru');

Route::get('/dashboard/guru/rekap-jadwal', [GuruDashboardController::class, 'rekapJadwal'])->middleware('webrole:guru');

Route::post('/dashboard/guru/finalisasi-mapel/{jadwalId}', [GuruActionController::class, 'finalisasiMapel'])->middleware('webrole:guru')->whereNumber('jadwalId');
Route::get('/dashboard/guru/absensi/{siswaId}/view', [GuruActionController::class, 'viewAbsensi'])->middleware('webrole:guru');
Route::get('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/view', [GuruActionController::class, 'viewAbsensiMapel'])->middleware('webrole:guru');
Route::get('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/edit', [GuruActionController::class, 'editAbsensiMapel'])->middleware('webrole:guru');
Route::post('/dashboard/guru/absensi-mapel/{jadwalId}/{siswaId}/update', [GuruActionController::class, 'updateAbsensiMapel'])->middleware('webrole:guru');
Route::get('/dashboard/guru/absensi/{siswaId}/edit', [GuruActionController::class, 'editAbsensi'])->middleware('webrole:guru');
Route::get('/dashboard/guru/pengajuan-izin', [GuruActionController::class, 'pengajuanIzin'])->middleware('webrole:guru');
Route::post('/dashboard/guru/absensi/{siswaId}/update', [GuruActionController::class, 'updateAbsensi'])->middleware('webrole:guru');

Route::post('/dashboard/guru/status/{id}', [GuruActionController::class, 'status'])->middleware('webrole:guru');
Route::get('/dashboard/guru/mulai-sesi/{jadwalId}', [GuruActionController::class, 'mulaiSesi'])->middleware('webrole:guru');

Route::get('/dashboard/guru/qr/{id}/view', [QrViewController::class, 'guruView'])->middleware('webrole:guru')->whereNumber('id');

Route::get('/dashboard/users', [SiswaDashboardController::class, 'index'])->middleware('webrole:siswa');
Route::post('/dashboard/users/izin/store', [SiswaDashboardController::class, 'storeIzin'])->middleware('webrole:siswa');

Route::post('/absensi/manual', [ManualAbsensiController::class, 'store']);

Route::middleware('webrole:admin')->group(function () {
    Route::get('/dashboard/admin/guru-piket', [GuruPiketController::class, 'index']);
    Route::get('/dashboard/admin/guru-piket/create', [GuruPiketController::class, 'create']);
    Route::get('/dashboard/admin/guru-piket/edit/{id}', [GuruPiketController::class, 'edit']);
    Route::post('/dashboard/admin/guru-piket/store', [GuruPiketController::class, 'store']);
    Route::post('/dashboard/admin/guru-piket/update/{id}', [GuruPiketController::class, 'update']);
    Route::get('/dashboard/admin/guru-piket/delete/{id}', [GuruPiketController::class, 'delete']);
    Route::get('/dashboard/admin/jurusan', [JurusanController::class, 'index']);
    Route::get('/dashboard/admin/jurusan/create', [JurusanController::class, 'create']);
    Route::post('/dashboard/admin/jurusan/store', [JurusanController::class, 'store']);
    Route::get('/dashboard/admin/jurusan/delete/{id}', [JurusanController::class, 'delete']);
});
