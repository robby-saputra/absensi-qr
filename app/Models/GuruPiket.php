<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuruPiket extends Model
{
    protected $fillable = [
        'guru_id',
        'hari'
    ];

    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}