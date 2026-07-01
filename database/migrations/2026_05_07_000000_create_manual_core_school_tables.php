<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel inti sekolah seperti mapel, jadwal, piket, QR mapel, dan nilai.
return new class extends Migration
{
    public function up(): void
    {
        // Mapel menyimpan daftar mata pelajaran.
        if (! Schema::hasTable('mapels')) {
            Schema::create('mapels', function (Blueprint $table) {
                $table->id();
                $table->string('nama_mapel', 100);
                $table->timestamps();
            });
        }

        // Jadwal pelajaran menghubungkan kelas, hari, jam, mapel, dan guru pengajar.
        if (! Schema::hasTable('jadwal_pelajarans')) {
            Schema::create('jadwal_pelajarans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
                $table->string('hari', 20);
                $table->time('jam_mulai');
                $table->time('jam_selesai');
                $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
                $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('guru_pengganti_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status_guru', ['normal', 'sakit', 'izin', 'inval', 'digantikan'])->default('normal');
                $table->string('alasan_tidak_hadir', 100)->nullable();
                $table->string('keterangan')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['hari', 'kelas_id']);
                $table->index(['guru_id', 'hari']);
            });
        }

        // Guru piket menyimpan jadwal piket harian dan guru pengganti.
        if (! Schema::hasTable('guru_pikets')) {
            Schema::create('guru_pikets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('guru_pengganti_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('guru_pengganti2_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('hari', ['senin', 'selasa', 'rabu', 'kamis', 'jumat']);
                $table->timestamps();
                $table->boolean('aktif')->default(true);
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->enum('status', ['Akan Bertugas', 'Sedang Bertugas', 'Izin', 'Sakit', 'Digantikan', 'Selesai'])->default('Akan Bertugas');
                $table->timestamp('status_dipilih_at')->nullable();
                $table->softDeletes();
                $table->index(['hari', 'aktif']);
            });
        }

        // Absensi mapel menyimpan scan siswa pada jadwal pelajaran tertentu.
        if (! Schema::hasTable('absensi_mapels')) {
            Schema::create('absensi_mapels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jadwal_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();
                $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
                $table->date('tanggal');
                $table->time('jam_scan')->nullable();
                $table->string('status', 50)->default('hadir');
                $table->text('catatan_guru')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['jadwal_id', 'tanggal']);
                $table->index(['siswa_id', 'tanggal']);
            });
        }

        // QR sesi dipakai untuk QR khusus absensi mapel.
        if (! Schema::hasTable('qr_sesis')) {
            Schema::create('qr_sesis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jadwal_id')->constrained('jadwal_pelajarans')->cascadeOnDelete();
                $table->date('tanggal');
                $table->string('token')->unique();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();
                $table->index(['jadwal_id', 'tanggal', 'aktif']);
            });
        }

        if (! Schema::hasTable('nilais')) {
            Schema::create('nilais', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
                $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $table->enum('jenis_nilai', ['Tugas', 'UH', 'UTS', 'UAS']);
                $table->integer('nilai')->nullable();
                $table->text('keterangan')->nullable();
                $table->string('semester', 20)->nullable();
                $table->string('tahun_ajaran', 20)->nullable();
                $table->timestamps();
                $table->index(['siswa_id', 'mapel_id']);
            });
        }

        if (! Schema::hasTable('nilai_semesters')) {
            Schema::create('nilai_semesters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('mapel_id')->constrained('mapels')->cascadeOnDelete();
                $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
                $table->string('semester', 20);
                $table->decimal('rata_formatif', 5, 2)->nullable();
                $table->decimal('rata_penugasan', 5, 2)->nullable();
                $table->decimal('rata_total', 5, 2)->nullable();
                $table->timestamps();
                $table->unique(['siswa_id', 'mapel_id', 'guru_id', 'kelas_id', 'semester'], 'nilai_semester_unique');
            });
        }

        if (! Schema::hasTable('nilai_details')) {
            Schema::create('nilai_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nilai_semester_id')->constrained('nilai_semesters')->cascadeOnDelete();
                $table->enum('jenis', ['formatif', 'penugasan']);
                $table->integer('pertemuan_ke');
                $table->integer('nilai');
                $table->timestamps();
                $table->unique(['nilai_semester_id', 'jenis', 'pertemuan_ke'], 'nilai_detail_unique');
            });
        }

        if (! Schema::hasTable('wali_kelas')) {
            Schema::create('wali_kelas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
                $table->timestamps();
                $table->unique('guru_id');
                $table->unique('kelas_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wali_kelas');
        Schema::dropIfExists('nilai_details');
        Schema::dropIfExists('nilai_semesters');
        Schema::dropIfExists('nilais');
        Schema::dropIfExists('qr_sesis');
        Schema::dropIfExists('absensi_mapels');
        Schema::dropIfExists('guru_pikets');
        Schema::dropIfExists('jadwal_pelajarans');
        Schema::dropIfExists('mapels');
    }
};
