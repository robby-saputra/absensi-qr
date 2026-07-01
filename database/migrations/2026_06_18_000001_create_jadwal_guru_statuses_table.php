<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel status harian guru mapel pada jadwal pelajaran.
return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini mencatat apakah guru utama hadir, izin, sakit, atau digantikan pada tanggal tertentu.
        if (! Schema::hasTable('jadwal_guru_statuses')) {
            Schema::create('jadwal_guru_statuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jadwal_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();
                $table->date('tanggal');
                $table->foreignId('guru_utama_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('guru_pengganti_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status_guru', ['normal', 'sakit', 'izin', 'inval', 'digantikan'])->default('normal');
                $table->string('alasan_tidak_hadir', 100)->nullable();
                $table->timestamp('status_dipilih_at')->nullable();
                $table->timestamps();

                $table->unique(['jadwal_id', 'tanggal'], 'jadwal_guru_status_unique');
                $table->index(['tanggal', 'guru_utama_id']);
                $table->index(['tanggal', 'guru_pengganti_id']);
            });
        }
    }

    public function down(): void
    {
        // Rollback menghapus tabel status guru mapel.
        Schema::dropIfExists('jadwal_guru_statuses');
    }
};
