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
        'generated_by',
        'guru_piket_team_key',
        'guru_piket_ids',
        'guru_piket_id',
        'active_teacher_id',
        'replacement_id',
        'replacement_order',
        'aktif',
        'deactivated_at',
    ];
}
