<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('jurusan')) {
            Schema::create('jurusan', function (Blueprint $table) {
                $table->id();
                $table->string('nama_jurusan', 100)->nullable();
                $table->string('kode_jurusan', 20)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('kelas')) {
            Schema::create('kelas', function (Blueprint $table) {
                $table->id();
                $table->string('nama_kelas', 100);
                $table->foreignId('wali_kelas_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('jurusan_id')->nullable()->constrained('jurusan')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('users', 'kelas_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'kelas_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kelas_id');
            });
        }

        Schema::dropIfExists('kelas');
        Schema::dropIfExists('jurusan');
    }
};
