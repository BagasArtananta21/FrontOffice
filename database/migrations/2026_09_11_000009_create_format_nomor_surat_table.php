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
    Schema::create('format_nomor_surat', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();
        $table->json('susunan');
        $table->date('berlaku_sejak');
        $table->string('dasar_perubahan')->nullable();
        $table->foreignUuid('dibuat_oleh')->constrained('users')->restrictOnDelete();
        $table->timestamps();

        $table->unique(['opd_id', 'berlaku_sejak']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('format_nomor_surat');
    }
};
