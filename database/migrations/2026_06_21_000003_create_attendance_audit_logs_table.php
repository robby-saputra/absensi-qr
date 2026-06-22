<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_audit_logs')) {
            Schema::create('attendance_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('role', 30)->nullable();
                $table->string('action', 40);
                $table->string('source', 30)->default('web');
                $table->string('table_name', 80);
                $table->unsignedBigInteger('record_id')->nullable();
                $table->json('before_data')->nullable();
                $table->json('after_data')->nullable();
                $table->text('reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
                $table->index(['table_name', 'record_id']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_audit_logs');
    }
};
