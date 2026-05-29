<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $request ??= request();
        $user = session('user');

        DB::table('audit_logs')->insert([
            'user_id' => $user->id ?? null,
            'user_name' => $user->nama ?? null,
            'user_role' => $user->role ?? null,
            'aksi' => $aksi,
            'tabel' => $tabel,
            'record_id' => $recordId,
            'judul' => $judul,
            'data_lama' => $dataLama !== null ? json_encode($dataLama, JSON_UNESCAPED_UNICODE) : null,
            'data_baru' => $dataBaru !== null ? json_encode($dataBaru, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 255),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
