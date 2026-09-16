<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomor_counters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();
            $table->foreignUuid('jenis_surat_id')->constrained('jenis_surat')->restrictOnDelete();

            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_terakhir')->default(0);
            $table->timestamps();

            $table->unique(['opd_id', 'jenis_surat_id', 'tahun'], 'counter_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomor_counters');
    }
};