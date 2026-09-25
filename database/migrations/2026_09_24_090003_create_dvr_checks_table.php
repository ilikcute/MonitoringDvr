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
        Schema::create('dvr_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dvr_id')->constrained('dvrs')->onDelete('cascade');
            $table->foreignId('checked_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('check_timestamp')->useCurrent();
            $table->boolean('is_ping_online')->default(true);
            $table->boolean('is_time_synced')->default(true);
            $table->integer('time_difference_seconds')->default(0);
            $table->enum('hdd_status', ['Normal', 'Error', 'Unformatted', 'Full'])->default('Normal');
            $table->unsignedSmallInteger('record_retention_days')->nullable()->comment('Estimasi hari rekaman tersedia');
            $table->unsignedTinyInteger('camera_working_count')->default(0);
            $table->unsignedTinyInteger('camera_broken_count')->default(0);
            $table->enum('network_type', ['LAN', 'WAN'])->default('LAN');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['dvr_id', 'check_timestamp'], 'idx_dvr_check_time');
            $table->index('checked_by_user_id', 'idx_checker');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dvr_checks');
    }
};
