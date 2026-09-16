<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opd')->restrictOnDelete();
            $table->string('kode_bidang', 20)->nullable();
            $table->string('nama_bidang');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->unique(['opd_id', 'nama_bidang']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bidang');
    }
};