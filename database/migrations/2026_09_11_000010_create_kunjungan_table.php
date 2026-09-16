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
        Schema::create('kunjungan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();
            $table->foreignUuid('tamu_id')->constrained('tamu')->restrictOnDelete();
            $table->foreignUuid('bidang_id')->nullable()->constrained('bidang')->restrictOnDelete();
            $table->foreignUuid('pegawai_id')->nullable()->constrained('pegawai')->restrictOnDelete();
            $table->foreignUuid('kategori_kunjungan_id')->nullable()->constrained('kategori_kunjungan')->restrictOnDelete();

            $table->text('keperluan')->nullable();
            $table->unsignedSmallInteger('jumlah_orang')->default(1);
            $table->timestamp('waktu_datang');
            $table->timestamp('waktu_keluar')->nullable();
            $table->string('status')->default('di_dalam'); // di_dalam || selesai || ditutup_otomatis
            $table->boolean('sudah_dihubungi')->default(false);
            $table->text('catatan_petugas')->nullable();
            $table->string('sumber_input')->default('display'); //display || manual
            $table->foreignUuid('dicatat_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['opd_id', 'status']);
            $table->index(['opd_id', 'waktu_datang']);
            $table->index(['opd_id', 'tamu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kunjungan');
    }
};
