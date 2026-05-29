<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajarans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 30);
            $table->enum('semester', ['ganjil', 'genap']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('aktif')->default(false);
            $table->timestamps();

            $table->unique(['nama', 'semester']);
            $table->index(['aktif', 'tanggal_mulai', 'tanggal_selesai']);
        });

        $today = now()->toDateString();
        $year = (int) now()->format('Y');
        $month = (int) now()->format('m');
        $startYear = $month >= 7 ? $year : $year - 1;
        $semester = $month >= 7 && $month <= 12 ? 'ganjil' : 'genap';

        DB::table('tahun_ajarans')->insert([
            'nama' => $startYear.'/'.($startYear + 1),
            'semester' => $semester,
            'tanggal_mulai' => $semester === 'ganjil' ? "{$startYear}-07-01" : ($startYear + 1).'-01-01',
            'tanggal_selesai' => $semester === 'ganjil' ? "{$startYear}-12-31" : ($startYear + 1).'-06-30',
            'aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_ajarans');
    }
};
