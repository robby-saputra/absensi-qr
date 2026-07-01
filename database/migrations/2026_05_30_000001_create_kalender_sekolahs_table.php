<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel kalender sekolah untuk libur, kegiatan, dan ujian.
return new class extends Migration
{
    public function up(): void
    {
        // Kalender sekolah dipakai untuk menentukan hari libur dan informasi agenda sekolah.
        Schema::create('kalender_sekolahs', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->string('judul');
            $table->enum('jenis', ['libur', 'kegiatan', 'ujian'])->default('libur');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        // Rollback menghapus tabel kalender sekolah.
        Schema::dropIfExists('kalender_sekolahs');
    }
};
