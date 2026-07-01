<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel status validasi bulanan untuk rekap absensi.
return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini menyimpan hasil pengecekan, jumlah masalah, dan status kunci rekap bulanan.
        if (! Schema::hasTable('monthly_validation_statuses')) {
            Schema::create('monthly_validation_statuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tahun_ajaran_id')->nullable()->constrained('tahun_ajarans')->nullOnDelete();
                $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
                $table->string('periode', 7);
                $table->string('scope', 20)->default('sekolah');
                $table->string('status', 30)->default('belum_dicek');
                $table->unsignedInteger('total_masalah')->default(0);
                $table->json('ringkasan')->nullable();
                $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('checked_at')->nullable();
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('locked_at')->nullable();
                $table->text('catatan')->nullable();
                $table->timestamps();
                $table->unique(['tahun_ajaran_id', 'kelas_id', 'periode', 'scope'], 'monthly_validation_unique');
            });
        }
    }

    public function down(): void
    {
        // Rollback menghapus tabel validasi bulanan.
        Schema::dropIfExists('monthly_validation_statuses');
    }
};
