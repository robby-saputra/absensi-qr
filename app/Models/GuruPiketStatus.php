<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuruPiketStatus extends Model
{
    // SoftDeletes membuat status lama bisa diarsipkan tanpa langsung hilang permanen.
    use SoftDeletes;

    // Model ini menyimpan status guru piket per tanggal.
    // Contohnya: hadir, izin, sakit, digantikan, atau selesai.
    protected $fillable = [
        'guru_piket_id', 'guru_id', 'tanggal', 'status', 'peran', 'menggantikan_guru_id', 'waktu_konfirmasi',
        'waktu_scan_masuk', 'waktu_scan_keluar', 'dipilih_oleh', 'sumber', 'keterangan',
    ];

    // Cast membuat kolom tanggal/waktu otomatis dibaca sebagai object date/datetime oleh Laravel.
    protected $casts = [
        'tanggal' => 'date',
        'waktu_konfirmasi' => 'datetime',
        'waktu_scan_masuk' => 'datetime',
        'waktu_scan_keluar' => 'datetime',
    ];
}
