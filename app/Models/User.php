<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'users';

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
        'aktif'

    ];

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
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function getKelasAttribute()
    {
        return $this->kelasRelasi?->nama_kelas;
    }

    /*
    |--------------------------------------------------------------------------
    | RELASI GURU PIKET
    |--------------------------------------------------------------------------
    */
    public function jadwalPiket()
    {
        return $this->hasMany(GuruPiket::class, 'guru_id');
    }
}
