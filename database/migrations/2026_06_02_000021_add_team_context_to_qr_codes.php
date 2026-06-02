<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qr_codes')) {
            return;
        }

        Schema::table('qr_codes', function (Blueprint $table) {
            if (! Schema::hasColumn('qr_codes', 'generated_by')) {
                $table->unsignedBigInteger('generated_by')->nullable()->after('expires_at');
            }

            if (! Schema::hasColumn('qr_codes', 'guru_piket_team_key')) {
                $table->string('guru_piket_team_key', 120)->nullable()->after('generated_by');
            }

            if (! Schema::hasColumn('qr_codes', 'guru_piket_ids')) {
                $table->text('guru_piket_ids')->nullable()->after('guru_piket_team_key');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('qr_codes')) {
            return;
        }

        Schema::table('qr_codes', function (Blueprint $table) {
            if (Schema::hasColumn('qr_codes', 'guru_piket_ids')) {
                $table->dropColumn('guru_piket_ids');
            }

            if (Schema::hasColumn('qr_codes', 'guru_piket_team_key')) {
                $table->dropColumn('guru_piket_team_key');
            }

            if (Schema::hasColumn('qr_codes', 'generated_by')) {
                $table->dropColumn('generated_by');
            }
        });
    }
};
