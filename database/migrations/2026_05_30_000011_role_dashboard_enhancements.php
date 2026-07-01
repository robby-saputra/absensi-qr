<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration ini menambahkan fitur pendukung dashboard role guru, piket, dan wali.
return new class extends Migration
{
    public function up(): void
    {
        // Catatan guru pada absensi mapel dipakai saat verifikasi kehadiran per pelajaran.
        if (Schema::hasTable('absensi_mapels')) {
            Schema::table('absensi_mapels', function (Blueprint $table) {
                if (! Schema::hasColumn('absensi_mapels', 'catatan_guru')) {
                    $table->text('catatan_guru')->nullable()->after('status');
                }
            });
        }

        // Catatan piket dipakai untuk memberi keterangan pada absensi harian.
        if (Schema::hasTable('absensis')) {
            Schema::table('absensis', function (Blueprint $table) {
                if (! Schema::hasColumn('absensis', 'catatan_piket')) {
                    $table->text('catatan_piket')->nullable()->after('status_pulang');
                }
            });
        }

        // Attendance session locks mengunci sesi rekap/absensi agar tidak berubah setelah final.
        if (! Schema::hasTable('attendance_session_locks')) {
            Schema::create('attendance_session_locks', function (Blueprint $table) {
                $table->id();
                $table->string('jenis', 30);
                $table->date('tanggal');
                $table->foreignId('jadwal_id')->nullable()->constrained('jadwal_pelajarans')->nullOnDelete();
                $table->foreignId('kelas_id')->nullable()->constrained('kelas')->nullOnDelete();
                $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 30)->default('final');
                $table->text('catatan')->nullable();
                $table->timestamps();
                $table->unique(['jenis', 'tanggal', 'jadwal_id', 'kelas_id'], 'attendance_locks_unique');
            });
        }

        // Wali followups menyimpan catatan pembinaan wali kelas terhadap siswa.
        if (! Schema::hasTable('wali_followups')) {
            Schema::create('wali_followups', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wali_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
                $table->date('tanggal');
                $table->string('kategori', 50)->default('pembinaan');
                $table->text('catatan');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Rollback menghapus tabel pendukung dan kolom catatan tambahan.
        Schema::dropIfExists('wali_followups');
        Schema::dropIfExists('attendance_session_locks');

        if (Schema::hasTable('absensi_mapels') && Schema::hasColumn('absensi_mapels', 'catatan_guru')) {
            Schema::table('absensi_mapels', function (Blueprint $table) {
                $table->dropColumn('catatan_guru');
            });
        }

        if (Schema::hasTable('absensis') && Schema::hasColumn('absensis', 'catatan_piket')) {
            Schema::table('absensis', function (Blueprint $table) {
                $table->dropColumn('catatan_piket');
            });
        }
    }
};
