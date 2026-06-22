<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jadwal_guru_replacements')) return;
        Schema::create('jadwal_guru_replacements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jadwal_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();
            $table->date('tanggal');
            $table->foreignId('guru_utama_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guru_pengganti_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('menggantikan_replacement_id')->nullable()->constrained('jadwal_guru_replacements')->nullOnDelete();
            $table->unsignedSmallInteger('urutan_penggantian');
            $table->string('status_penugasan', 30)->default('menunggu_konfirmasi');
            $table->string('status_kehadiran', 30)->nullable();
            $table->text('alasan')->nullable();
            $table->foreignId('ditunjuk_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mulai_aktif_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['jadwal_id', 'tanggal', 'guru_pengganti_id'], 'jadwal_replacement_teacher_unique');
            $table->index(['jadwal_id', 'tanggal', 'status_penugasan'], 'jadwal_replacement_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_guru_replacements');
    }
};
