<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan field filter tahun ajaran, provinsi, dan sumber pada kalender sekolah.
return new class extends Migration
{
    public function up(): void
    {
        // Field tambahan membuat kalender bisa dibedakan berdasarkan periode dan wilayah.
        Schema::table('kalender_sekolahs', function (Blueprint $table) {
            if (! Schema::hasColumn('kalender_sekolahs', 'tahun_ajaran_id')) {
                $table->foreignId('tahun_ajaran_id')->nullable()->after('id')->constrained('tahun_ajarans')->nullOnDelete();
            }

            if (! Schema::hasColumn('kalender_sekolahs', 'provinsi')) {
                $table->string('provinsi', 80)->nullable()->after('jenis');
            }

            if (! Schema::hasColumn('kalender_sekolahs', 'sumber')) {
                $table->string('sumber', 40)->default('manual')->after('provinsi');
            }
        });
    }

    public function down(): void
    {
        // Rollback menghapus field filter kalender jika ada.
        Schema::table('kalender_sekolahs', function (Blueprint $table) {
            if (Schema::hasColumn('kalender_sekolahs', 'tahun_ajaran_id')) {
                $table->dropConstrainedForeignId('tahun_ajaran_id');
            }

            if (Schema::hasColumn('kalender_sekolahs', 'provinsi')) {
                $table->dropColumn('provinsi');
            }

            if (Schema::hasColumn('kalender_sekolahs', 'sumber')) {
                $table->dropColumn('sumber');
            }
        });
    }
};
