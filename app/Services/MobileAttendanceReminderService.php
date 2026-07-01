<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Service ini mengirim pengingat mobile untuk absen mapel dan absen pulang.
class MobileAttendanceReminderService
{
    // Menjalankan pengiriman pengingat berdasarkan waktu saat ini.
    public function run(?Carbon $moment = null): array
    {
        $now = ($moment ?: now())->copy()->timezone(config('app.timezone', 'Asia/Jakarta'));
        $tanggal = $now->toDateString();
        $hari = strtolower($now->locale('id')->translatedFormat('l'));
        $time = $now->format('H:i:s');
        $result = ['mapel' => 0, 'pulang' => 0];

        // Jika tabel dispatch belum ada atau hari libur, pengingat tidak dikirim.
        if (! Schema::hasTable('mobile_reminder_dispatches') || $this->isHoliday($tanggal)) {
            return $result;
        }

        // Mengambil jadwal mapel yang sedang berjalan pada jam sekarang.
        $activeSchedules = DB::table('jadwal_pelajarans as jp')
            ->join('mapels as m', 'm.id', '=', 'jp.mapel_id')
            ->whereRaw('LOWER(jp.hari) = ?', [$hari])
            ->where('jp.jam_mulai', '<=', $time)
            ->where('jp.jam_selesai', '>=', $time)
            ->whereNull('jp.deleted_at')
            ->select('jp.id', 'jp.kelas_id', 'jp.jam_mulai', 'jp.jam_selesai', 'jp.jam_ke_mulai', 'jp.jumlah_jp', 'm.nama_mapel')
            ->get();

        foreach ($activeSchedules as $schedule) {
            // Setiap siswa hanya boleh mendapat satu pengingat untuk jadwal yang sama.
            $students = DB::table('users')->where('role', 'siswa')->where('aktif', 1)->where('kelas_id', $schedule->kelas_id)->pluck('id');
            foreach ($students as $studentId) {
                $key = "mapel:{$tanggal}:{$schedule->id}:{$studentId}";
                if (! $this->claim($key, 'mapel', $tanggal, (int) $studentId, (int) $schedule->id)) continue;

                $jpEnd = $schedule->jam_ke_mulai && $schedule->jumlah_jp
                    ? ($schedule->jam_ke_mulai + $schedule->jumlah_jp - 1)
                    : null;
                $jp = $schedule->jam_ke_mulai ? 'JP '.$schedule->jam_ke_mulai.($jpEnd && $jpEnd !== $schedule->jam_ke_mulai ? '–'.$jpEnd : '') : 'sesi ini';
                $sent = kirimNotifikasiMobile((int) $studentId, 'Mapel Sedang Berlangsung',
                    "Sekarang {$schedule->nama_mapel} ({$jp}), pukul ".substr($schedule->jam_mulai, 0, 5).'–'.substr($schedule->jam_selesai, 0, 5).'. Jangan lupa absen mapel.',
                    ['tipe' => 'pengingat_mapel', 'jadwal_id' => $schedule->id, 'tanggal' => $tanggal]);
                if ($sent) $result['mapel']++;
                else $this->release($key);
            }
        }

        // Pengingat pulang dikirim pada rentang waktu pendek agar tidak berulang terus.
        if ($now->format('H:i') >= '14:00' && $now->format('H:i') <= '14:04') {
            $students = DB::table('users')->where('role', 'siswa')->where('aktif', 1)->pluck('id');
            foreach ($students as $studentId) {
                $key = "pulang:{$tanggal}:{$studentId}";
                if (! $this->claim($key, 'pulang', $tanggal, (int) $studentId, null)) continue;
                $sent = kirimNotifikasiMobile((int) $studentId, 'Waktunya Absen Pulang',
                    'Sudah pukul 14.00. Waktunya melakukan scan QR untuk absen pulang.',
                    ['tipe' => 'pengingat_pulang', 'tanggal' => $tanggal, 'jam' => '14:00']);
                if ($sent) $result['pulang']++;
                else $this->release($key);
            }
        }

        return $result;
    }

    // Claim mencegah pengiriman notifikasi ganda untuk dispatch key yang sama.
    private function claim(string $key, string $type, string $tanggal, int $studentId, ?int $scheduleId): bool
    {
        return DB::table('mobile_reminder_dispatches')->insertOrIgnore([
            'dispatch_key' => $key, 'type' => $type, 'tanggal' => $tanggal,
            'siswa_id' => $studentId, 'jadwal_id' => $scheduleId, 'sent_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]) === 1;
    }

    // Release dipakai jika pengiriman gagal sehingga bisa dicoba lagi pada run berikutnya.
    private function release(string $key): void
    {
        DB::table('mobile_reminder_dispatches')->where('dispatch_key', $key)->delete();
    }

    // Hari libur dicek dari kalender siswa agar pengingat tidak dikirim saat sekolah libur.
    private function isHoliday(string $date): bool
    {
        return function_exists('apiKalenderSiswa')
            && apiKalenderSiswa($date, $date)->contains(fn ($event) => ($event['jenis'] ?? null) === 'libur');
    }
}
