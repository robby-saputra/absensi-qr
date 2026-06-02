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
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('nama');
        $table->string('nis', 30)->nullable();
        $table->string('nuptk', 50)->nullable();
        $table->string('username')->unique();
        $table->string('password');
        $table->enum('role', ['admin', 'guru', 'piket', 'siswa']);
        $table->string('admin_level', 30)->nullable();
        $table->unsignedBigInteger('wali_kelas_id')->nullable();
        $table->string('no_ortu')->nullable();
        $table->string('nama_ortu')->nullable();
        $table->rememberToken();
        $table->timestamps();
        $table->boolean('aktif')->default(true);
        $table->softDeletes();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
