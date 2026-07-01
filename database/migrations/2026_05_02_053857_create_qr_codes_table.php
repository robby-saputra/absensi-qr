<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel QR harian untuk absensi masuk dan pulang.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Setiap QR punya tanggal, tipe scan, dan token unik yang dibaca aplikasi.
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->enum('tipe', ['masuk', 'pulang']);
            $table->string('token');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback menghapus tabel QR harian.
        Schema::dropIfExists('qr_codes');
    }
};
