<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';

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
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI JURUSAN
    |--------------------------------------------------------------------------
    */
    public function jurusan()
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI SISWA
    |--------------------------------------------------------------------------
    */
    public function siswa()
    {
        return $this->hasMany(User::class, 'kelas_id');
    }
}
