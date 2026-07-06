<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('jadwal_pelajarans', 'status_dipilih_at')) {
            Schema::table('jadwal_pelajarans', function (Blueprint $table) {
                $table->timestamp('status_dipilih_at')->nullable()->after('status_guru');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('jadwal_pelajarans', 'status_dipilih_at')) {
            Schema::table('jadwal_pelajarans', function (Blueprint $table) {
                $table->dropColumn('status_dipilih_at');
            });
        }
    }
};
