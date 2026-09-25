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
        Schema::create('dvr_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dvr_id')->constrained('dvrs')->onDelete('cascade');
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->unsignedTinyInteger('account_slot')->comment('Slot 1 sd 5 (IC, EDP, SPV, DEV, AUD)');
            $table->string('username', 50);
            $table->text('encrypted_password')->comment('Payload AES-256 via Laravel Crypt::encryptString');
            $table->string('permission_profile', 100)->comment('Hak akses: Live View, Playback, Admin, dll');
            $table->boolean('is_active')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['dvr_id', 'department_id'], 'uk_dvr_department');
            $table->unique(['dvr_id', 'account_slot'], 'uk_dvr_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dvr_accounts');
    }
};
