<?php

use App\Models\User;
use App\Services\AttendanceSettingService;
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

if (! function_exists('absensiLewatBatasEdit')) {
    function absensiLewatBatasEdit(string $tanggal, ?string $jamBatas = null): bool
    {
        $jamBatas = $jamBatas ?: AttendanceSettingService::jamKunciAbsensi();

        return now()->greaterThanOrEqualTo(Carbon::parse($tanggal.' '.$jamBatas));
    }
}

if (! function_exists('jamKunciAbsensiLabel')) {
    function jamKunciAbsensiLabel(): string
    {
        return substr(AttendanceSettingService::jamKunciAbsensi(), 0, 5);
    }
}

if (! function_exists('pesanAbsensiTerkunciOtomatis')) {
    function pesanAbsensiTerkunciOtomatis(): string
    {
        return 'Absensi sudah dikunci otomatis setelah pukul '.jamKunciAbsensiLabel().'. Silakan hubungi admin untuk koreksi data.';
    }
}

if (! function_exists('absensiTerkunciUntukNonAdmin')) {
    function absensiTerkunciUntukNonAdmin(string $jenis, string $tanggal, ?int $jadwalId = null, ?int $kelasId = null): bool
    {
        return absensiLewatBatasEdit($tanggal);
    }
}

if (! function_exists('kelasAksesGuruIds')) {
    function kelasAksesGuruIds(int $guruId)
    {
        return DB::table('jadwal_pelajarans')
            ->where('guru_id', $guruId)
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

if (! function_exists('hapusDataAdmin')) {
    function hapusDataAdmin(string $table, int $id, string $judul, Request $request): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $before = DB::table($table)->where('id', $id)->first();
        if (! $before) {
            return false;
        }

        DB::table($table)->where('id', $id)->delete();

        return true;
    }
}

if (! function_exists('hapusMassalAdmin')) {
    function hapusMassalAdmin(string $resource, array $ids, Request $request): array
    {
        $resources = [
            'kalender-sekolah' => ['table' => 'kalender_sekolahs', 'label' => 'Kalender sekolah'],
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

            if (hapusDataAdmin($config['table'], $id, $config['label'], $request)) {
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

if (! function_exists('jalankanAutoAlfaHarian')) {
    function jalankanAutoAlfaHarian(?string $tanggal = null): array
    {
        $tanggal ??= now()->toDateString();
        $libur = hariLiburSekolah($tanggal);
        if ($libur) {
            return ['created' => 0, 'skipped' => 'libur'];
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

                    foreach (array_filter([$jadwal->guru_id]) as $guruId) {
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


        return [
            'harian' => $createdHarian,
            'mapel' => $createdMapel,
            'guru_notified' => $notifiedGuru->count(),
        ];
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
        ]));

        $guruBentrok = DB::table('jadwal_pelajarans')
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->whereIn('guru_id', $guruIds);

        if ($guruIds && ($bentrok = jamBentrok($guruBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            $namaGuru = User::whereIn('id', $guruIds)
                ->where('id', $bentrok->guru_id)
                ->value('nama') ?: 'Guru tersebut';

            return $namaGuru.' sudah memiliki jadwal mengajar pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        $guruPiketBentrok = DB::table('guru_pikets')
            ->when($tahunAjaranId && Schema::hasColumn('guru_pikets', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where('aktif', 1)
            ->whereIn('guru_id', $guruIds);

        if ($guruIds && ($bentrok = jamBentrok($guruPiketBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            $namaGuru = User::whereIn('id', $guruIds)
                ->where('id', $bentrok->guru_id)
                ->value('nama') ?: 'Guru tersebut';

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
        $guruIds = array_values(array_unique(array_filter((array) $request->guru_id)));

        $piketBentrok = DB::table('guru_pikets')
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->when($tahunAjaranId && Schema::hasColumn('guru_pikets', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->where('aktif', 1)
            ->whereIn('guru_id', $guruIds);

        if ($guruIds && ($bentrok = jamBentrok($piketBentrok, $request->jam_mulai, $request->jam_selesai)->first())) {
            return 'Ada guru yang sudah memiliki jadwal piket pada '.$request->hari.' '.$bentrok->jam_mulai.'-'.$bentrok->jam_selesai.'.';
        }

        $jadwalBentrok = DB::table('jadwal_pelajarans')
            ->when($tahunAjaranId && Schema::hasColumn('jadwal_pelajarans', 'tahun_ajaran_id'), fn ($query) => $query->where('tahun_ajaran_id', $tahunAjaranId))
            ->whereRaw('LOWER(hari) = ?', [$hari])
            ->whereIn('guru_id', $guruIds);

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
