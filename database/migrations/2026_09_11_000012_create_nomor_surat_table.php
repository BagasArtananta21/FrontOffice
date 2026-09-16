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
            $table->id();
            $table->foreignId('opd_id')->constrained('opd')->restrictOnDelete();
            $table->foreignId('jenis_surat_id')->constrained('jenis_surat')->restrictOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_urut');
            $table->string('nomor_lengkap');
            $table->string('perihal');
            $table->string('tujuan_surat')->nullable();
            $table->foreignId('bidang_id')->nullable()->constrained('bidang')->restrictOnDelete();
            $table->string('nama_peminta')->nullable();
            $table->date('tanggal_surat');
            $table->string('status', 20)->default('terbit');
            $table->string('alasan_batal')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['opd_id', 'jenis_surat_id', 'tahun', 'nomor_urut'], 'nomor_unik');
            $table->index(['opd_id', 'tahun']);
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
