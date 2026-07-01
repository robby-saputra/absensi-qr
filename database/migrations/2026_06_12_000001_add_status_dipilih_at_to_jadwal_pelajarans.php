<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan waktu pilih status pada jadwal pelajaran guru.
return new class extends Migration
{
    public function up(): void
    {
        // Kolom ini dipakai untuk mengunci status guru mapel setelah dipilih.
        if (! Schema::hasColumn('jadwal_pelajarans', 'status_dipilih_at')) {
            Schema::table('jadwal_pelajarans', function (Blueprint $table) {
                $table->timestamp('status_dipilih_at')->nullable()->after('status_guru');
            });
        }
    }

    public function down(): void
    {
        // Rollback menghapus kolom waktu pilih status guru mapel.
        if (Schema::hasColumn('jadwal_pelajarans', 'status_dipilih_at')) {
            Schema::table('jadwal_pelajarans', function (Blueprint $table) {
                $table->dropColumn('status_dipilih_at');
            });
        }
    }
};
