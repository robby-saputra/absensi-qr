<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini memperluas token FCM dan membuat tabel riwayat pengingat mobile.
return new class extends Migration
{
    public function up(): void
    {
        // Audience membedakan token orang tua dengan audience lain jika nanti dibutuhkan.
        Schema::table('parent_fcm_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('parent_fcm_tokens', 'audience')) {
                $table->string('audience', 20)->default('orang_tua')->after('siswa_id')->index();
            }
        });

        // Dispatch table mencegah notifikasi pengingat dikirim berkali-kali pada jadwal yang sama.
        if (! Schema::hasTable('mobile_reminder_dispatches')) {
            Schema::create('mobile_reminder_dispatches', function (Blueprint $table) {
                $table->id();
                $table->string('dispatch_key', 160)->unique();
                $table->string('type', 30);
                $table->date('tanggal');
                $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('jadwal_id')->nullable()->constrained('jadwal_pelajarans')->cascadeOnDelete();
                $table->timestamp('sent_at');
                $table->timestamps();
                $table->index(['tanggal', 'type']);
            });
        }
    }

    public function down(): void
    {
        // Rollback menghapus dispatch pengingat dan kolom audience token.
        Schema::dropIfExists('mobile_reminder_dispatches');
        Schema::table('parent_fcm_tokens', function (Blueprint $table) {
            if (Schema::hasColumn('parent_fcm_tokens', 'audience')) {
                $table->dropColumn('audience');
            }
        });
    }
};
