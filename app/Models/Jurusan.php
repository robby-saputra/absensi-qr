<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    // Model ini terhubung ke tabel jurusan.
    // Jurusan dipakai untuk mengelompokkan kelas berdasarkan program keahlian.
    protected $table = 'jurusan';

    // Kolom yang boleh diisi dari halaman admin jurusan.
    protected $fillable = [
        'nama_jurusan',
        'kode_jurusan',
    ];
}
