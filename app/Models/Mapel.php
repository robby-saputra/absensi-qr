<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    use HasFactory;

    // Model ini terhubung ke tabel mapels.
    // Data mapel dipakai saat admin membuat jadwal pelajaran.
    protected $table = 'mapels';

    // Kolom yang boleh diisi dari form mata pelajaran.
    protected $fillable = [
        'nama',
        'kode',
    ];
}
