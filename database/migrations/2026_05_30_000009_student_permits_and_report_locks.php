<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_permit_requests')) {
            Schema::create('student_permit_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai');
                $table->string('jenis', 20);
                $table->text('alasan')->nullable();
                $table->string('bukti_path')->nullable();
                $table->string('status', 30)->default('menunggu');
                $table->text('catatan_review')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('deleted_at')->nullable()->index();
                $table->timestamps();
                $table->index(['siswa_id', 'tanggal_mulai', 'tanggal_selesai'], 'permit_siswa_tgl_idx');
            });
        }

        if (! Schema::hasTable('rekap_locks')) {
            Schema::create('rekap_locks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('jenis_rekap', 40);
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai');
                $table->string('keterangan')->nullable();
                $table->timestamp('locked_at')->nullable();
                $table->timestamp('deleted_at')->nullable()->index();
                $table->timestamps();
                $table->unique(['jenis_rekap', 'tanggal_mulai', 'tanggal_selesai'], 'rekap_locks_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_locks');
        Schema::dropIfExists('student_permit_requests');
    }
};
