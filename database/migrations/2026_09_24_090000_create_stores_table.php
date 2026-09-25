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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('store_code', 20)->unique()->comment('Format: T001 s.d T666');
            $table->string('store_name', 150);
            $table->string('region', 50)->comment('Region 1, Region 2, Jabodetabek, dll');
            $table->text('address')->nullable();
            $table->string('ip_subnet', 45)->nullable()->comment('Contoh: 10.120.45.0/24');
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->enum('status', ['Active', 'Renovation', 'Closed'])->default('Active');
            $table->boolean('allow_extra_dvr')->default(false)->comment('Override batas kapasitas maksimal 2 unit');
            $table->timestamps();

            $table->index('store_code', 'idx_store_code');
            $table->index('region', 'idx_region');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
