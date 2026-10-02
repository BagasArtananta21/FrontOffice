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
            $table->foreignUuid('bidang_id')->nullable()->constrained('bidang')->restrictOnDelete();
            $table->foreignUuid('pegawai_id')->nullable()->constrained('pegawai')->restrictOnDelete();
            
            $table->string('nama_tamu');
            $table->string('jenis_kelamin', 10);
            $table->string('instansi_asal')->nullable();
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->text('keperluan')->nullable();
            $table->string('tanda_tangan')->nullable();
            $table->timestamp('waktu_datang');
            $table->boolean('sudah_dihubungi')->default(false);
            $table->text('catatan_petugas')->nullable();
            $table->string('sumber_input')->default('display'); //display || manual
            $table->foreignUuid('dicatat_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['opd_id', 'waktu_datang']);
            $table->index(['opd_id', 'nama_tamu']);
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
