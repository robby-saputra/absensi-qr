<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'aktif')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('aktif')->default(true)->after('kelas_id');
            });
        }

        if (! Schema::hasTable('attendance_settings')) {
            Schema::create('attendance_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('value');
                $table->timestamps();
            });

            DB::table('attendance_settings')->insert([
                [
                    'key' => 'jam_masuk',
                    'value' => '07:00:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'jam_pulang',
                    'value' => '14:00:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'aktif')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('aktif');
            });
        }

        Schema::dropIfExists('attendance_settings');
    }
};
