<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            if (! Schema::hasColumn('qr_codes', 'guru_piket_id')) $table->foreignId('guru_piket_id')->nullable()->after('id')->constrained('guru_pikets')->nullOnDelete();
            if (! Schema::hasColumn('qr_codes', 'active_teacher_id')) $table->foreignId('active_teacher_id')->nullable()->after('guru_piket_id')->constrained('users')->nullOnDelete();
            if (! Schema::hasColumn('qr_codes', 'replacement_id')) $table->foreignId('replacement_id')->nullable()->after('active_teacher_id')->constrained('guru_piket_replacements')->nullOnDelete();
            if (! Schema::hasColumn('qr_codes', 'replacement_order')) $table->unsignedSmallInteger('replacement_order')->nullable()->after('replacement_id');
            if (! Schema::hasColumn('qr_codes', 'aktif')) $table->boolean('aktif')->default(true)->after('expires_at')->index();
            if (! Schema::hasColumn('qr_codes', 'deactivated_at')) $table->timestamp('deactivated_at')->nullable()->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            foreach (['deactivated_at', 'aktif', 'replacement_order', 'replacement_id', 'active_teacher_id', 'guru_piket_id'] as $column) {
                if (Schema::hasColumn('qr_codes', $column)) $table->dropColumn($column);
            }
        });
    }
};
