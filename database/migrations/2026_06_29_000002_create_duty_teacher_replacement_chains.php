<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat rantai pengganti untuk guru piket.
return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini mencatat pengganti pertama dan pengganti lanjutan jika ada yang berhalangan.
        Schema::create('guru_piket_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_piket_id')->constrained('guru_pikets')->cascadeOnDelete();
            $table->date('tanggal');
            $table->foreignId('guru_utama_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guru_pengganti_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('menggantikan_replacement_id')->nullable()->constrained('guru_piket_replacements')->nullOnDelete();
            $table->unsignedSmallInteger('urutan_penggantian')->default(1);
            $table->string('status_penugasan', 30)->default('menunggu_konfirmasi');
            $table->text('alasan')->nullable();
            $table->foreignId('ditunjuk_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mulai_aktif_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->string('active_unique_key', 160)->nullable();
            $table->index(['tanggal', 'status_penugasan']);
            $table->index(['guru_pengganti_id', 'tanggal']);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE guru_piket_replacements MODIFY active_unique_key VARCHAR(160) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN CONCAT(guru_piket_id, '|', guru_pengganti_id, '|', tanggal) ELSE NULL END) STORED");
            DB::statement('ALTER TABLE guru_piket_replacements ADD UNIQUE KEY duty_replacement_active_unique (active_unique_key)');
        }
        // Setting cutoff menentukan batas waktu guru piket harus konfirmasi.
        DB::table('attendance_settings')->updateOrInsert(
            ['key' => 'teacher_attendance_cutoff'],
            ['value' => '07:00', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        // Rollback menghapus rantai pengganti piket dan setting cutoff.
        Schema::dropIfExists('guru_piket_replacements');
        DB::table('attendance_settings')->where('key', 'teacher_attendance_cutoff')->delete();
    }
};
