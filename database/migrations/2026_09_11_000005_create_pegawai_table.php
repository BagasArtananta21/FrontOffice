<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();
            $table->foreignUuid('bidang_id')->nullable()->constrained('bidang')->restrictOnDelete();
            $table->string('nip')->nullable();
            $table->string('nama_pegawai');
            $table->string('jabatan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('pegawai');
    }
};
