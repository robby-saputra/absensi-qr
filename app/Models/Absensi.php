<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    // Model ini menyimpan absensi harian siswa, yaitu jam masuk dan jam pulang.
    // Data ini menjadi dasar dashboard, riwayat, rekap, dan laporan harian.
    protected $fillable = [
        // ID siswa yang melakukan absensi.
        'id_siswa',

        // Tahun ajaran disimpan agar laporan bisa difilter berdasarkan periode sekolah.
        'tahun_ajaran_id',

        // Tanggal absensi harian.
        'tanggal',

        // Jam masuk dan jam pulang dari hasil scan QR.
        'jam_masuk',
        'jam_pulang',

        // Status masuk/pulang, misalnya hadir, telat, pulang, atau pulang_cepat.
        'status_masuk',
        'status_pulang',
    ];
}
