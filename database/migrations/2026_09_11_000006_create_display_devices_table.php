<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('display_devices', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();
        $table->string('nama');
        $table->string('token_hash', 64)->nullable()->unique();
        $table->text('pin')->nullable();
        $table->string('pin_hmac', 64)->nullable()->unique();
        $table->foreignUuid('kunjungan_menunggu_id')->nullable()->constrained('kunjungan')->nullOnDelete();
        $table->timestamp('terakhir_aktif')->nullable();
        $table->timestamp('muat_ulang_diminta_pada')->nullable();

        $table->boolean('aktif')->default(false);
        $table->timestamps();

        $table->unique('opd_id');
    });
    }

    public function down(): void
    {
        Schema::dropIfExists('display_devices');
    }
};