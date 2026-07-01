<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan field JP pada jadwal pelajaran.
return new class extends Migration
{
    public function up(): void
    {
        // JP membantu jadwal ditulis berdasarkan jam pelajaran, bukan hanya jam manual.
        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            if (! Schema::hasColumn('jadwal_pelajarans', 'jam_ke_mulai')) {
                $table->unsignedTinyInteger('jam_ke_mulai')->nullable()->after('hari');
            }

            if (! Schema::hasColumn('jadwal_pelajarans', 'jumlah_jp')) {
                $table->unsignedTinyInteger('jumlah_jp')->nullable()->after('jam_ke_mulai');
            }
        });
    }

    public function down(): void
    {
        // Rollback menghapus field JP dari jadwal pelajaran.
        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            if (Schema::hasColumn('jadwal_pelajarans', 'jumlah_jp')) {
                $table->dropColumn('jumlah_jp');
            }

            if (Schema::hasColumn('jadwal_pelajarans', 'jam_ke_mulai')) {
                $table->dropColumn('jam_ke_mulai');
            }
        });
    }
};
