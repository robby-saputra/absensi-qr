<?php

namespace App\Support;

use Illuminate\Http\Request;

class AuditLogger
{
    public static function record(
        string $aksi,
        ?string $tabel = null,
        ?int $recordId = null,
        ?string $judul = null,
        mixed $dataLama = null,
        mixed $dataBaru = null,
        ?Request $request = null
    ): void {
        // Audit log detail sudah keluar dari scope skripsi absensi.
    }
}
