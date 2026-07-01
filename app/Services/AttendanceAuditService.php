<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Service ini mencatat jejak perubahan data absensi untuk kebutuhan audit.
class AttendanceAuditService
{
    // Menyimpan data sebelum dan sesudah perubahan ke tabel attendance_audit_logs jika tabel tersedia.
    public function record(string $action, string $table, ?int $recordId, mixed $before, mixed $after, ?Request $request = null, ?string $reason = null): void
    {
        // Guard ini membuat sistem tetap aman jika tabel audit belum ada di database.
        if (! Schema::hasTable('attendance_audit_logs')) {
            return;
        }

        $user = $request?->user() ?: auth()->user();
        // Data audit menyimpan aktor, sumber request, nama tabel, ID record, dan snapshot perubahan.
        DB::table('attendance_audit_logs')->insert([
            'user_id' => $user?->id,
            'role' => $user?->role,
            'action' => strtolower($action),
            'source' => $request?->is('api/*') ? 'api' : ($request ? 'web' : 'system'),
            'table_name' => $table,
            'record_id' => $recordId,
            'before_data' => $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'after_data' => $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'reason' => $reason,
            'ip_address' => $request?->ip(),
            'user_agent' => substr((string) $request?->userAgent(), 0, 500) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
