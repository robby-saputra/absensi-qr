<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    // Model ini menyimpan QR absensi harian yang dibuat oleh guru piket.
    // QR bisa bertipe masuk atau pulang dan memiliki masa berlaku.
    protected $fillable = [
        // Tanggal QR dibuat.
        'tanggal',

        // Tipe QR: masuk atau pulang.
        'tipe',

        // Token unik yang discan oleh aplikasi siswa.
        'token',

        // Batas waktu QR masih boleh dipakai.
        'expires_at',

        // User yang membuat QR.
        'generated_by',

        // Data guru piket aktif yang berhubungan dengan QR.
        'guru_piket_team_key',
        'guru_piket_ids',
        'guru_piket_id',
        'active_teacher_id',

        // Data pengganti jika QR dibuat oleh guru piket pengganti.
        'replacement_id',
        'replacement_order',

        // Penanda QR masih aktif atau sudah dibatalkan.
        'aktif',
        'deactivated_at',
    ];
}
