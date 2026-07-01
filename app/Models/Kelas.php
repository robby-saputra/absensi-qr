<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    // Model ini terhubung ke tabel kelas.
    // Data kelas dipakai untuk mengelompokkan siswa dan jadwal pelajaran.
    protected $table = 'kelas';

    // Kolom yang boleh diisi dari form admin.
    protected $fillable = [
        'nama_kelas',
        'wali_kelas_id',
        'jurusan_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI WALI KELAS
    |--------------------------------------------------------------------------
    */
    public function waliKelas()
    {
        // Satu kelas dapat memiliki satu guru sebagai wali kelas.
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI JURUSAN
    |--------------------------------------------------------------------------
    */
    public function jurusan()
    {
        // Kelas dapat terhubung ke satu jurusan.
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI SISWA
    |--------------------------------------------------------------------------
    */
    public function siswa()
    {
        // Satu kelas memiliki banyak siswa.
        return $this->hasMany(User::class, 'kelas_id');
    }
}
