<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('api_access_tokens')) {
            Schema::create('api_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role_context', 30);
                $table->char('token_hash', 64)->unique();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->string('device_name')->nullable();
                $table->ipAddress('ip_address')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'role_context']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('api_access_tokens');
    }
};
