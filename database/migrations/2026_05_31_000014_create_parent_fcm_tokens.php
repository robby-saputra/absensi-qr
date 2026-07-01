<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel token FCM untuk notifikasi orang tua.
return new class extends Migration
{
    public function up(): void
    {
        // Token disimpan per siswa agar orang tua bisa menerima notifikasi mobile.
        if (! Schema::hasTable('parent_fcm_tokens')) {
            Schema::create('parent_fcm_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
                $table->string('token', 500)->unique();
                $table->string('device_name')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->index('siswa_id');
            });
        }
    }

    public function down(): void
    {
        // Rollback menghapus tabel token FCM orang tua.
        Schema::dropIfExists('parent_fcm_tokens');
    }
};
