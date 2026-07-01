<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

// Migration ini membuat tabel status harian guru piket.
return new class extends Migration
{
    public function up(): void
    {
        // Tabel guru_piket_statuses mencatat konfirmasi hadir/izin/sakit guru piket per tanggal.
        if (! Schema::hasTable('guru_piket_statuses')) {
            Schema::create('guru_piket_statuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('guru_piket_id')->constrained('guru_pikets')->cascadeOnDelete();
                $table->date('tanggal');
                $table->string('status', 30)->default('belum_konfirmasi');
                $table->timestamp('waktu_konfirmasi')->nullable();
                $table->timestamp('waktu_scan_masuk')->nullable();
                $table->timestamp('waktu_scan_keluar')->nullable();
                $table->foreignId('dipilih_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->string('sumber', 30)->default('web');
                $table->text('keterangan')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->string('active_unique_key', 100)->nullable();
                $table->index(['tanggal', 'status']);
            });
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE guru_piket_statuses MODIFY active_unique_key VARCHAR(100) GENERATED ALWAYS AS (CASE WHEN deleted_at IS NULL THEN CONCAT(guru_piket_id, '|', tanggal) ELSE NULL END) STORED");
                DB::statement('ALTER TABLE guru_piket_statuses ADD UNIQUE KEY guru_piket_status_tanggal_unique (active_unique_key)');
            } else {
                Schema::table('guru_piket_statuses', fn (Blueprint $table) => $table->unique(['guru_piket_id', 'tanggal'], 'guru_piket_status_tanggal_unique'));
            }
        }

        // Hanya migrasikan status lama yang mempunyai timestamp konfirmasi tepercaya.
        if (Schema::hasColumn('guru_pikets', 'status_dipilih_at')) {
            DB::table('guru_pikets')->whereNotNull('status_dipilih_at')->orderBy('id')->each(function ($row) {
                $status = match (strtolower((string) $row->status)) {
                    'sedang bertugas', 'hadir' => 'hadir',
                    'izin' => 'izin',
                    'sakit' => 'sakit',
                    'selesai' => 'selesai',
                    default => null,
                };

                if (! $status) {
                    Log::warning('Status guru piket lama tidak dimigrasikan karena tidak dikenali.', ['guru_piket_id' => $row->id, 'status' => $row->status]);
                    return;
                }

                DB::table('guru_piket_statuses')->updateOrInsert(
                    ['guru_piket_id' => $row->id, 'tanggal' => substr((string) $row->status_dipilih_at, 0, 10)],
                    ['status' => $status, 'waktu_konfirmasi' => $row->status_dipilih_at, 'dipilih_oleh' => $row->guru_id, 'sumber' => 'migrasi_status_lama', 'updated_at' => now(), 'created_at' => now()]
                );
            });
        }
    }

    public function down(): void
    {
        // Rollback menghapus tabel status guru piket.
        Schema::dropIfExists('guru_piket_statuses');
    }
};
