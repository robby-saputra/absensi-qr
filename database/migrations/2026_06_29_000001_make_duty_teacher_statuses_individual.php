<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration ini mengubah status guru piket agar tercatat per guru, bukan hanya per jadwal.
return new class extends Migration
{
    public function up(): void
    {
        // Menambahkan guru_id, peran, dan guru yang digantikan pada status piket.
        Schema::table('guru_piket_statuses', function (Blueprint $table) {
            $table->foreignId('guru_id')->nullable()->after('guru_piket_id')->constrained('users')->nullOnDelete();
            $table->string('peran', 20)->default('utama')->after('status');
            $table->foreignId('menggantikan_guru_id')->nullable()->after('peran')->constrained('users')->nullOnDelete();
        });

        // Data lama diisi sebagai status guru utama berdasarkan jadwal piket.
        DB::table('guru_piket_statuses as gps')->join('guru_pikets as gp', 'gp.id', '=', 'gps.guru_piket_id')
            ->whereNull('gps.guru_id')->update(['gps.guru_id' => DB::raw('gp.guru_id'), 'gps.peran' => 'utama']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE guru_piket_statuses DROP INDEX guru_piket_status_tanggal_unique');
            DB::statement("ALTER TABLE guru_piket_statuses MODIFY active_unique_key VARCHAR(140) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN CONCAT(guru_piket_id, '|', guru_id, '|', tanggal) ELSE NULL END) STORED");
            DB::statement('ALTER TABLE guru_piket_statuses ADD UNIQUE KEY guru_piket_status_guru_tanggal_unique (active_unique_key)');
        }

        Schema::table('guru_piket_statuses', fn (Blueprint $table) => $table->index(['guru_id', 'tanggal', 'status'], 'guru_piket_status_guru_date_idx'));
    }

    public function down(): void
    {
        // Rollback mengembalikan unique key lama dan menghapus field individual.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE guru_piket_statuses DROP INDEX guru_piket_status_guru_tanggal_unique');
            DB::statement("ALTER TABLE guru_piket_statuses MODIFY active_unique_key VARCHAR(100) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN CONCAT(guru_piket_id, '|', tanggal) ELSE NULL END) STORED");
            DB::statement('ALTER TABLE guru_piket_statuses ADD UNIQUE KEY guru_piket_status_tanggal_unique (active_unique_key)');
        }
        Schema::table('guru_piket_statuses', function (Blueprint $table) {
            $table->dropIndex('guru_piket_status_guru_date_idx');
            $table->dropConstrainedForeignId('menggantikan_guru_id');
            $table->dropColumn('peran');
            $table->dropConstrainedForeignId('guru_id');
        });
    }
};
