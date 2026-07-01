<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan konfirmasi guru pengganti pada status guru mapel.
return new class extends Migration
{
    public function up(): void
    {
        // Jika tabel status guru belum ada, perubahan dilewati.
        if (! Schema::hasTable('jadwal_guru_statuses')) {
            return;
        }

        // Field pengganti mencatat apakah guru pengganti menerima tugas atau tidak.
        Schema::table('jadwal_guru_statuses', function (Blueprint $table) {
            if (! Schema::hasColumn('jadwal_guru_statuses', 'pengganti_status')) {
                $table->string('pengganti_status', 30)->nullable()->after('status_dipilih_at');
            }

            if (! Schema::hasColumn('jadwal_guru_statuses', 'pengganti_alasan')) {
                $table->string('pengganti_alasan', 100)->nullable()->after('pengganti_status');
            }

            if (! Schema::hasColumn('jadwal_guru_statuses', 'pengganti_dipilih_at')) {
                $table->timestamp('pengganti_dipilih_at')->nullable()->after('pengganti_alasan');
            }
        });
    }

    public function down(): void
    {
        // Rollback menghapus field konfirmasi guru pengganti.
        if (! Schema::hasTable('jadwal_guru_statuses')) {
            return;
        }

        Schema::table('jadwal_guru_statuses', function (Blueprint $table) {
            foreach (['pengganti_dipilih_at', 'pengganti_alasan', 'pengganti_status'] as $column) {
                if (Schema::hasColumn('jadwal_guru_statuses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
