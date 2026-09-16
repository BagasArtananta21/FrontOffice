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
        Schema::create('tamu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opd_id')->constrained('opd')->restrictOnDelete();
            $table->string('nama_tamu');
            $table->string('jenis_kelamin', 10);
            $table->string('no_hp', 15);
            $table->string('instansi_asal')->nullable();
            $table->string('alamat')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->timestamps();

            $table->index(['opd_id', 'nama_tamu']);
            $table->index(['opd_id', 'no_hp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tamu');
    }
};
