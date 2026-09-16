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
        Schema::create('kategori_kunjungan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();
            $table->string('nama_kategori');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);

            $table->timestamps();

            $table->unique(['opd_id', 'nama_kategori']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_kunjungan');
    }
};
