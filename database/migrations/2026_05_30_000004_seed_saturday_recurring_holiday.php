<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kalender_sekolahs')) {
            return;
        }

        $exists = DB::table('kalender_sekolahs')
            ->where('jenis', 'libur')
            ->where('berulang', 1)
            ->where('hari_berulang', 'sabtu')
            ->exists();

        if (! $exists) {
            DB::table('kalender_sekolahs')->insert([
                'tahun_ajaran_id' => null,
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->toDateString(),
                'judul' => 'Libur Rutin Sabtu',
                'jenis' => 'libur',
                'provinsi' => 'Banten',
                'sumber' => 'sistem',
                'berulang' => 1,
                'hari_berulang' => 'sabtu',
                'keterangan' => 'Sekolah libur setiap hari Sabtu.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('kalender_sekolahs')) {
            return;
        }

        DB::table('kalender_sekolahs')
            ->where('judul', 'Libur Rutin Sabtu')
            ->where('sumber', 'sistem')
            ->delete();
    }
};
