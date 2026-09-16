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
    Schema::create('jenis_surat', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();

        $table->string('kode_klasifikasi', 20);   // contoh: 005, 800
        $table->string('nama');
        // contoh isi: {klasifikasi}/{urut}/{kode_opd}/{tahun}
        // pola final menunggu wawancara FO
        $table->string('format_template');
        $table->unsignedTinyInteger('panjang_urut')->default(0);  // 0 = tanpa padding nol
        $table->boolean('reset_tahunan')->default(true);
        $table->boolean('aktif')->default(true);
        $table->timestamps();

        $table->unique(['opd_id', 'kode_klasifikasi']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_surat');
    }
};
