<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('guru_pikets', 'status_dipilih_at')) {
            Schema::table('guru_pikets', function (Blueprint $table) {
                $table->timestamp('status_dipilih_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('guru_pikets', 'status_dipilih_at')) {
            Schema::table('guru_pikets', function (Blueprint $table) {
                $table->dropColumn('status_dipilih_at');
            });
        }
    }
};
