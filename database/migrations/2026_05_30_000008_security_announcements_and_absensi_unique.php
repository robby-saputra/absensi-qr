<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('absensis')) {
            DB::statement('UPDATE absensis a JOIN (SELECT MIN(id) id, id_siswa, tanggal FROM absensis WHERE deleted_at IS NULL GROUP BY id_siswa, tanggal HAVING COUNT(*) > 1) keep_row ON keep_row.id_siswa = a.id_siswa AND keep_row.tanggal = a.tanggal SET a.deleted_at = NOW(), a.updated_at = NOW() WHERE a.deleted_at IS NULL AND a.id <> keep_row.id');
            if (! Schema::hasColumn('absensis', 'active_unique_key')) {
                DB::statement("ALTER TABLE absensis ADD active_unique_key VARCHAR(120) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN CONCAT(id_siswa, '|', tanggal) ELSE NULL END) STORED");
                DB::statement('ALTER TABLE absensis ADD UNIQUE KEY absensis_active_unique (active_unique_key)');
            }
        }

        if (Schema::hasTable('absensi_mapels')) {
            DB::statement('UPDATE absensi_mapels a JOIN (SELECT MIN(id) id, jadwal_id, siswa_id, tanggal FROM absensi_mapels WHERE deleted_at IS NULL GROUP BY jadwal_id, siswa_id, tanggal HAVING COUNT(*) > 1) keep_row ON keep_row.jadwal_id = a.jadwal_id AND keep_row.siswa_id = a.siswa_id AND keep_row.tanggal = a.tanggal SET a.deleted_at = NOW(), a.updated_at = NOW() WHERE a.deleted_at IS NULL AND a.id <> keep_row.id');
            if (! Schema::hasColumn('absensi_mapels', 'active_unique_key')) {
                DB::statement("ALTER TABLE absensi_mapels ADD active_unique_key VARCHAR(160) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN CONCAT(jadwal_id, '|', siswa_id, '|', tanggal) ELSE NULL END) STORED");
                DB::statement('ALTER TABLE absensi_mapels ADD UNIQUE KEY absensi_mapels_active_unique (active_unique_key)');
            }
        }

        if (! Schema::hasTable('login_security_events')) {
            Schema::create('login_security_events', function (Blueprint $table) {
                $table->id();
                $table->string('username')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type', 40);
                $table->unsignedInteger('attempt_count')->default(1);
                $table->ipAddress('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->text('keterangan')->nullable();
                $table->timestamps();
                $table->index(['event_type', 'created_at']);
            });
        }

        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('judul');
                $table->text('isi');
                $table->string('target_role', 30)->default('semua');
                $table->string('kategori', 40)->default('info');
                $table->date('tanggal_mulai')->nullable();
                $table->date('tanggal_selesai')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamp('deleted_at')->nullable()->index();
                $table->timestamps();
                $table->index(['target_role', 'aktif']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('login_security_events');
        if (Schema::hasColumn('absensis', 'active_unique_key')) {
            DB::statement('ALTER TABLE absensis DROP INDEX absensis_active_unique');
            DB::statement('ALTER TABLE absensis DROP COLUMN active_unique_key');
        }
        if (Schema::hasColumn('absensi_mapels', 'active_unique_key')) {
            DB::statement('ALTER TABLE absensi_mapels DROP INDEX absensi_mapels_active_unique');
            DB::statement('ALTER TABLE absensi_mapels DROP COLUMN active_unique_key');
        }
    }
};
