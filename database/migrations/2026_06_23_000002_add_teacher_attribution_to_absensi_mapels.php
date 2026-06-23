<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('absensi_mapels')) {
            return;
        }

        Schema::table('absensi_mapels', function (Blueprint $table) {
            if (! Schema::hasColumn('absensi_mapels', 'guru_utama_id')) {
                $table->foreignId('guru_utama_id')->nullable()->after('siswa_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('absensi_mapels', 'guru_pelaksana_id')) {
                $table->foreignId('guru_pelaksana_id')->nullable()->after('guru_utama_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('absensi_mapels', 'role_guru_pelaksana')) {
                $table->string('role_guru_pelaksana', 40)->nullable()->after('guru_pelaksana_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('absensi_mapels')) {
            return;
        }

        Schema::table('absensi_mapels', function (Blueprint $table) {
            if (Schema::hasColumn('absensi_mapels', 'role_guru_pelaksana')) {
                $table->dropColumn('role_guru_pelaksana');
            }

            if (Schema::hasColumn('absensi_mapels', 'guru_pelaksana_id')) {
                $table->dropConstrainedForeignId('guru_pelaksana_id');
            }

            if (Schema::hasColumn('absensi_mapels', 'guru_utama_id')) {
                $table->dropConstrainedForeignId('guru_utama_id');
            }
        });
    }
};
