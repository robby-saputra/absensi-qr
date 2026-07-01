<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini memastikan users memiliki kolom nama orang tua.
return new class extends Migration
{
    public function up(): void
    {
        // Kolom nama_ortu dipakai untuk akun atau informasi orang tua siswa.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nama_ortu')) {
                $table->string('nama_ortu')->nullable()->after('no_ortu');
            }
        });
    }

    public function down(): void
    {
        // Rollback menghapus kolom nama_ortu jika kolom tersebut ada.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nama_ortu')) {
                $table->dropColumn('nama_ortu');
            }
        });
    }
};
