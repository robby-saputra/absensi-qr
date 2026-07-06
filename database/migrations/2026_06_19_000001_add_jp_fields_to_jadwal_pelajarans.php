<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
