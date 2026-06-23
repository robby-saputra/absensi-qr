<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiswaDashboardGuruAktifTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_active_teacher_for_three_admin_schedule_slots(): void
    {
        $this->setTrustedNow('2026-06-23 07:30:00');
        DB::table('kalender_sekolahs')->delete();

        $kelasId = DB::table('kelas')->insertGetId(['nama_kelas' => 'X IPA 1', 'created_at' => now(), 'updated_at' => now()]);
        $bahasaIndonesiaId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Bahasa Indonesia', 'created_at' => now(), 'updated_at' => now()]);
        $bahasaInggrisId = DB::table('mapels')->insertGetId(['nama_mapel' => 'Bahasa Inggris', 'created_at' => now(), 'updated_at' => now()]);
        $johnId = $this->user('JOHNCENA', 'guru');
        $mayaId = $this->user('Maya Anggraini', 'guru');
        $teguhId = $this->user('Teguh Firmansyah', 'guru');
        $robbyId = $this->user('ROBBY', 'guru');
        $siswaId = $this->user('Siswa Test', 'siswa', ['kelas_id' => $kelasId, 'nis' => '1001']);
        DB::table('tahun_ajarans')->update(['aktif' => 0]);
        $tahunId = DB::table('tahun_ajarans')->insertGetId([
            'nama' => '2026/2027',
            'semester' => 'ganjil',
            'tanggal_mulai' => '2026-06-01',
            'tanggal_selesai' => '2026-12-31',
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('jadwal_pelajarans')->insert([
            [
                'kelas_id' => $kelasId,
                'tahun_ajaran_id' => $tahunId,
                'hari' => 'selasa',
                'jam_mulai' => '07:00:00',
                'jam_selesai' => '08:10:00',
                'jam_ke_mulai' => 1,
                'jumlah_jp' => 2,
                'mapel_id' => $bahasaIndonesiaId,
                'guru_id' => $johnId,
                'status_guru' => 'normal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kelas_id' => $kelasId,
                'tahun_ajaran_id' => $tahunId,
                'hari' => 'selasa',
                'jam_mulai' => '08:10:00',
                'jam_selesai' => '09:20:00',
                'jam_ke_mulai' => 3,
                'jumlah_jp' => 2,
                'mapel_id' => $bahasaInggrisId,
                'guru_id' => $mayaId,
                'status_guru' => 'normal',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $jadwalSakitId = DB::table('jadwal_pelajarans')->insertGetId([
            'kelas_id' => $kelasId,
            'tahun_ajaran_id' => $tahunId,
            'hari' => 'selasa',
            'jam_mulai' => '09:20:00',
            'jam_selesai' => '10:30:00',
            'jam_ke_mulai' => 5,
            'jumlah_jp' => 2,
            'mapel_id' => $bahasaIndonesiaId,
            'guru_id' => $teguhId,
            'guru_pengganti_id' => $robbyId,
            'status_guru' => 'sakit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('jadwal_guru_statuses')->insert([
            'jadwal_id' => $jadwalSakitId,
            'tanggal' => '2026-06-23',
            'guru_utama_id' => $teguhId,
            'guru_pengganti_id' => $robbyId,
            'status_guru' => 'sakit',
            'alasan_tidak_hadir' => 'Sakit',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('jadwal_guru_replacements')->insert([
            'jadwal_id' => $jadwalSakitId,
            'tanggal' => '2026-06-23',
            'guru_utama_id' => $teguhId,
            'guru_pengganti_id' => $robbyId,
            'urutan_penggantian' => 1,
            'status_penugasan' => 'aktif',
            'status_kehadiran' => 'hadir',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = 'token-siswa-dashboard';
        DB::table('api_access_tokens')->insert([
            'user_id' => $siswaId,
            'role_context' => 'siswa',
            'token_hash' => hash('sha256', $token),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/siswa/dashboard/'.$siswaId);

        $response->assertOk()
            ->assertJsonPath('jadwal_hari_ini.0.guru_utama', 'JOHNCENA')
            ->assertJsonPath('jadwal_hari_ini.0.guru_aktif', 'JOHNCENA')
            ->assertJsonPath('jadwal_hari_ini.1.guru_utama', 'Maya Anggraini')
            ->assertJsonPath('jadwal_hari_ini.1.guru_aktif', 'Maya Anggraini')
            ->assertJsonPath('jadwal_hari_ini.2.guru_utama', 'Teguh Firmansyah')
            ->assertJsonPath('jadwal_hari_ini.2.status_guru_utama', 'sakit')
            ->assertJsonPath('jadwal_hari_ini.2.status_guru_utama_label', 'Sakit')
            ->assertJsonPath('jadwal_hari_ini.2.guru_aktif', 'ROBBY')
            ->assertJsonPath('jadwal_hari_ini.2.guru_aktif_id', $robbyId)
            ->assertJsonPath('jadwal_hari_ini.2.role_guru_aktif', 'pengganti_pertama')
            ->assertJsonPath('jadwal_hari_ini.2.role_guru_aktif_label', 'Guru Pengganti')
            ->assertJsonPath('jadwal_hari_ini.2.pengganti_terbaru', 'ROBBY')
            ->assertJsonPath('jadwal_hari_ini.2.status_penugasan', 'aktif')
            ->assertJsonPath('jadwal_hari_ini.2.butuh_pengganti', false)
            ->assertJsonPath('jadwal_hari_ini.2.guru_tersedia', true)
            ->assertJsonPath('jadwal_hari_ini.2.riwayat_pengganti.0.nama', 'ROBBY')
            ->assertJsonPath('jadwal_hari_ini.2.riwayat_pengganti.0.status_penugasan', 'aktif')
            ->assertJsonPath('ringkasan_kehadiran_hari_ini.status', 'belum_absen')
            ->assertJsonPath('statistik_bulan_berjalan.hadir', 0)
            ->assertJsonPath('statistik_bulan_berjalan.terlambat', 0)
            ->assertJsonPath('aktivitas_terbaru', [])
            ->assertJsonPath('notifikasi_penting.0.pesan', 'Perhatian: Anak belum melakukan absensi masuk hari ini.');

        DB::table('absensis')->insert([
            'id_siswa' => $siswaId,
            'tahun_ajaran_id' => $tahunId,
            'tanggal' => '2026-06-23',
            'jam_masuk' => '06:55:00',
            'status_masuk' => 'hadir',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('qr_sesis')->insert([
            'jadwal_id' => $jadwalSakitId,
            'tanggal' => '2026-06-23',
            'token' => 'qr-mapel-robby',
            'aktif' => 1,
            'expires_at' => '2026-06-23 10:30:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $scanResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/scan-mapel', [
                'token' => 'qr-mapel-robby',
                'latitude' => -6.172564,
                'longitude' => 106.627565,
            ]);
        $scanResponse->assertOk();
        $this->assertSame('success', $scanResponse->json('status'), $scanResponse->getContent());
        $scanResponse
            ->assertJsonPath('guru_utama', 'Teguh Firmansyah')
            ->assertJsonPath('guru_pelaksana', 'ROBBY')
            ->assertJsonPath('role_guru_pelaksana', 'pengganti_pertama');

        $this->assertDatabaseHas('absensi_mapels', [
            'jadwal_id' => $jadwalSakitId,
            'siswa_id' => $siswaId,
            'tanggal' => '2026-06-23',
            'guru_utama_id' => $teguhId,
            'guru_pelaksana_id' => $robbyId,
            'role_guru_pelaksana' => 'pengganti_pertama',
        ]);

        $refreshed = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/siswa/dashboard/'.$siswaId);

        $refreshed->assertOk()
            ->assertJsonPath('ringkasan_kehadiran_hari_ini.status', 'hadir')
            ->assertJsonPath('ringkasan_kehadiran_hari_ini.jam_masuk', '06:55')
            ->assertJsonPath('statistik_bulan_berjalan.hadir', 1)
            ->assertJsonPath('aktivitas_terbaru.0.tipe', 'mapel')
            ->assertJsonPath('aktivitas_terbaru.0.mapel', 'Bahasa Indonesia');
    }

    private function user(string $nama, string $role, array $extra = []): int
    {
        return DB::table('users')->insertGetId($extra + [
            'nama' => $nama,
            'username' => strtolower(str_replace(' ', '_', $nama)).uniqid(),
            'password' => 'secret',
            'role' => $role,
            'aktif' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function setTrustedNow(string $dateTime): void
    {
        $now = Carbon::parse($dateTime, 'Asia/Jakarta');
        Carbon::setTestNow($now);
        Cache::put('trusted_server_timestamp', $now->timestamp, 30);
        Cache::put('trusted_server_timestamp_cached_at', time(), 30);
    }
}
