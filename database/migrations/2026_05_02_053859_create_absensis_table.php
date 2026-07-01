<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel absensi harian siswa.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel absensis menyimpan jam masuk, jam pulang, dan status harian setiap siswa.
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_siswa')->constrained('users');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('status_masuk')->nullable();
            $table->string('status_pulang')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback menghapus tabel absensi harian.
        Schema::dropIfExists('absensis');
    }
};
