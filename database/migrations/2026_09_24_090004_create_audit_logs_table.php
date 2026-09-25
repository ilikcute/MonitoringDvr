<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50)->comment('AUTH_LOGIN_SUCCESS, CREDENTIAL_REVEAL, DVR_IP_CHANGED, dll');
            $table->string('target_type', 50)->nullable()->comment('Store, Dvr, DvrAccount');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('department_code', 20)->nullable()->comment('Departemen akun jika terkait intip password');
            $table->enum('network_type', ['LAN', 'WAN'])->default('LAN');
            $table->string('ip_address', 45);
            $table->string('user_agent', 255)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'created_at'], 'idx_action_time');
            $table->index(['user_id', 'created_at'], 'idx_user_action');
            $table->index(['target_type', 'target_id'], 'idx_target');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
