<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat atau melengkapi tabel notifikasi sistem.
return new class extends Migration
{
    public function up(): void
    {
        // Jika tabel notifications belum ada, tabel dasar dibuat terlebih dahulu.
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('judul');
                $table->text('pesan')->nullable();
                $table->string('status', 30)->default('belum_dibaca');
                $table->timestamps();
            });
        }

        // Field detail dipakai untuk kategori, tingkat penting, sumber data, dan payload notifikasi.
        Schema::table('notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('notifications', 'kategori')) {
                $table->string('kategori', 60)->default('sistem')->after('status')->index();
            }

            if (! Schema::hasColumn('notifications', 'severity')) {
                $table->string('severity', 30)->default('info')->after('kategori');
            }

            if (! Schema::hasColumn('notifications', 'source_type')) {
                $table->string('source_type', 80)->nullable()->after('severity');
            }

            if (! Schema::hasColumn('notifications', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }

            if (! Schema::hasColumn('notifications', 'payload')) {
                $table->json('payload')->nullable()->after('source_id');
            }
        });
    }

    public function down(): void
    {
        // Rollback hanya menghapus field detail tambahan, bukan tabel notifikasi dasar.
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            foreach (['payload', 'source_id', 'source_type', 'severity', 'kategori'] as $column) {
                if (Schema::hasColumn('notifications', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
