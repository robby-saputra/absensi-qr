<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel cache untuk penyimpanan sementara Laravel.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabel cache menyimpan data sementara berdasarkan key dan waktu kedaluwarsa.
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        // Cache lock dipakai Laravel agar proses tertentu tidak berjalan bersamaan.
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback menghapus tabel cache dan lock-nya.
        Schema::dropIfExists('cache');
        Schema::dropIfExists('cache_locks');
    }
};
