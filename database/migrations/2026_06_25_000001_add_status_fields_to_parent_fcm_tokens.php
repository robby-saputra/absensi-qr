<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('parent_fcm_tokens')) {
            return;
        }

        Schema::table('parent_fcm_tokens', function (Blueprint $table) {
            if (! Schema::hasColumn('parent_fcm_tokens', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('device_name')->index();
            }
            if (! Schema::hasColumn('parent_fcm_tokens', 'last_error_code')) {
                $table->string('last_error_code', 80)->nullable()->after('last_used_at');
            }
            if (! Schema::hasColumn('parent_fcm_tokens', 'last_error_message')) {
                $table->string('last_error_message', 500)->nullable()->after('last_error_code');
            }
            if (! Schema::hasColumn('parent_fcm_tokens', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('last_error_message');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('parent_fcm_tokens')) {
            return;
        }

        Schema::table('parent_fcm_tokens', function (Blueprint $table) {
            foreach (['failed_at', 'last_error_message', 'last_error_code', 'is_active'] as $column) {
                if (Schema::hasColumn('parent_fcm_tokens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
