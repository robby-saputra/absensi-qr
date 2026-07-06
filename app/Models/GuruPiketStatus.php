<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuruPiketStatus extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'guru_piket_id', 'guru_id', 'tanggal', 'status', 'peran', 'menggantikan_guru_id', 'waktu_konfirmasi',
        'waktu_scan_masuk', 'waktu_scan_keluar', 'dipilih_oleh', 'sumber', 'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'waktu_konfirmasi' => 'datetime',
        'waktu_scan_masuk' => 'datetime',
        'waktu_scan_keluar' => 'datetime',
    ];
}
