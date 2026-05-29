<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kalender_sekolahs', function (Blueprint $table) {
            if (! Schema::hasColumn('kalender_sekolahs', 'berulang')) {
                $table->boolean('berulang')->default(false)->after('sumber');
            }

            if (! Schema::hasColumn('kalender_sekolahs', 'hari_berulang')) {
                $table->string('hari_berulang', 20)->nullable()->after('berulang');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kalender_sekolahs', function (Blueprint $table) {
            if (Schema::hasColumn('kalender_sekolahs', 'hari_berulang')) {
                $table->dropColumn('hari_berulang');
            }

            if (Schema::hasColumn('kalender_sekolahs', 'berulang')) {
                $table->dropColumn('berulang');
            }
        });
    }
};
