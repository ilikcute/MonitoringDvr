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
        Schema::create('dvrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->unsignedTinyInteger('dvr_index')->default(1)->comment('1 untuk DVR 1, 2 untuk DVR 2');
            $table->string('label', 100)->comment('Contoh: DVR 1 - Area Toko & Kasir');
            $table->string('brand', 60)->default('Hikvision');
            $table->string('model_series', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('ip_address', 45)->default('192.168.25.200');
            $table->unsignedInteger('http_port')->default(80);
            $table->unsignedInteger('rtsp_port')->default(554);
            $table->unsignedInteger('server_port')->default(8000)->comment('Client/Media port SDK');
            $table->unsignedTinyInteger('total_channels')->default(8);
            $table->decimal('storage_capacity_tb', 4, 1)->nullable();
            $table->unsignedSmallInteger('retention_days')->nullable()->comment('Estimasi lama hari penyimpanan rekaman');
            $table->string('firmware_version', 50)->nullable();
            $table->enum('status', ['Online', 'Offline', 'Degraded', 'Maintenance', 'Decommissioned'])->default('Offline');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'dvr_index'], 'uk_store_dvr_index');
            $table->index('ip_address', 'idx_ip_address');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dvrs');
    }
};
