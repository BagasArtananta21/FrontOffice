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
        Schema::create('nomor_surat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();
            $table->foreignUuid('format_nomor_surat_id')->constrained('format_nomor_surat')->restrictOnDelete();
            $table->foreignUuid('bidang_id')->nullable()->constrained('bidang')->restrictOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_urut')->default(0);
            $table->unsignedSmallInteger('sub_nomor')->default(0);
            $table->string('nomor_lengkap')->nullable();
            $table->string('kode_klasifikasi', 20);
            $table->string('perihal');
            $table->string('tujuan_surat')->nullable();
            $table->string('nama_peminta')->nullable();
            $table->date('tanggal_surat');
            $table->timestamp('tanggal_terbit');
            $table->string('status', 20)->default('terbit');
            $table->string('alasan_batal')->nullable();
            $table->foreignUuid('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('dibatalkan_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->timestamp('batal_pada')->nullable();

            $table->unique(['opd_id', 'tahun', 'nomor_urut', 'sub_nomor'], 'nomor_unit');
            $table->index(['opd_id', 'tanggal_terbit']);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomor_surat');
    }
};
