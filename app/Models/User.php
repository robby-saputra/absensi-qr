<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Model ini terhubung ke tabel users.
    // Tabel users dipakai untuk admin, guru, guru piket, siswa, dan data orang tua siswa.
    protected $table = 'users';

    // Fillable adalah daftar kolom yang boleh diisi lewat create/update Eloquent.
    // Ini membantu Laravel mencegah pengisian kolom sembarangan.
    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | DATA USER
        |--------------------------------------------------------------------------
        */
        'nama',
        'nis',
        'username',
        'password',
        'role',
        'admin_level',

        /*
        |--------------------------------------------------------------------------
        | RELASI KELAS
        |--------------------------------------------------------------------------
        */
        'kelas_id',

        /*
        |--------------------------------------------------------------------------
        | DATA TAMBAHAN
        |--------------------------------------------------------------------------
        */
        'remember_token',
        'no_ortu',
        'nama_ortu',
        'nuptk',
        'aktif',

    ];

    // Hidden adalah data sensitif yang tidak ikut muncul saat model diubah menjadi array/JSON.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELASI KELAS
    |--------------------------------------------------------------------------
    */
    public function kelasRelasi()
    {
        // Setiap siswa bisa terhubung ke satu kelas lewat kolom kelas_id.
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function getKelasAttribute()
    {
        // Accessor ini membuat $user->kelas langsung mengembalikan nama kelas.
        return $this->kelasRelasi?->nama_kelas;
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI GURU PIKET
    |--------------------------------------------------------------------------
    */
    public function jadwalPiket()
    {
        // Guru dapat memiliki banyak jadwal piket.
        return $this->hasMany(GuruPiket::class, 'guru_id');
    }
}
