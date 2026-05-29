<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrCode extends Model
{
    protected $fillable = [
        'tanggal',
        'tipe',
        'token',
        'expires_at',
    ];
}
