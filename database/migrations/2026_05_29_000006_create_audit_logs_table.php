<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel audit_logs untuk mencatat perubahan penting oleh user.
return new class extends Migration
{
    public function up(): void
    {
        // Audit log menyimpan aktor, aksi, tabel, data lama, data baru, IP, dan user agent.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('user_role', 30)->nullable();
            $table->string('aksi', 80);
            $table->string('tabel', 80)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('judul')->nullable();
            $table->json('data_lama')->nullable();
            $table->json('data_baru')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['tabel', 'record_id']);
            $table->index(['aksi', 'created_at']);
        });
    }

    public function down(): void
    {
        // Rollback menghapus tabel audit log.
        Schema::dropIfExists('audit_logs');
    }
};
