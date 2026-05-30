<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('temporary_delegations')) {
            Schema::create('temporary_delegations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role_context', 30);
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai');
                $table->text('alasan')->nullable();
                $table->string('status', 30)->default('aktif');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('internal_messages')) {
            Schema::create('internal_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('siswa_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('kategori', 50)->default('catatan_siswa');
                $table->string('judul')->nullable();
                $table->text('pesan');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_messages');
        Schema::dropIfExists('temporary_delegations');
    }
};
