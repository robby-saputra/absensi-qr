<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuruPiket extends Model
{
    // Model ini menyimpan jadwal guru piket berdasarkan hari.
    protected $fillable = [
        // ID guru yang mendapat jadwal piket.
        'guru_id',

        // Hari piket, misalnya senin, selasa, dan seterusnya.
        'hari',
    ];

    public function guru()
    {
        // Jadwal piket dimiliki oleh satu user/guru.
        return $this->belongsTo(User::class, 'guru_id');
    }
}
