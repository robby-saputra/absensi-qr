<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan waktu kapan status guru piket dipilih.
return new class extends Migration
{
    public function up(): void
    {
        // Kolom status_dipilih_at membantu mengunci pilihan status agar tidak berubah-ubah.
        if (! Schema::hasColumn('guru_pikets', 'status_dipilih_at')) {
            Schema::table('guru_pikets', function (Blueprint $table) {
                $table->timestamp('status_dipilih_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        // Rollback menghapus kolom waktu pilih status jika ada.
        if (Schema::hasColumn('guru_pikets', 'status_dipilih_at')) {
            Schema::table('guru_pikets', function (Blueprint $table) {
                $table->dropColumn('status_dipilih_at');
            });
        }
    }
};
