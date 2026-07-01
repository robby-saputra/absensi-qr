<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel status login untuk memantau user yang sedang online.
return new class extends Migration
{
    public function up(): void
    {
        // Setiap user memiliki satu baris status online, waktu login, heartbeat terakhir, dan informasi perangkat.
        Schema::create('user_login_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('role', 30)->nullable();
            $table->boolean('is_online')->default(false);
            $table->timestamp('login_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('logout_at')->nullable();
            $table->string('ip_address', 60)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['is_online', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        // Rollback menghapus tabel status login user.
        Schema::dropIfExists('user_login_statuses');
    }
};
