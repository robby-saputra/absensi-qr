<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'nilai_details',
            'nilai_semesters',
            'nilais',
            'internal_messages',
            'temporary_delegations',
            'monthly_validation_statuses',
            'rekap_locks',
            'attendance_session_locks',
            'wali_followups',
            'announcements',
            'login_security_events',
            'audit_logs',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Tabel fitur lama tidak dibuat ulang otomatis karena sudah keluar dari scope skripsi absensi.
    }
};
