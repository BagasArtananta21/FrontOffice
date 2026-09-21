<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('display_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('opd_id')->constrained('opd')->restrictOnDelete();
            $table->string('nama');                       
            $table->string('token_hash', 64)->unique();
            $table->boolean('tampilkan_form')->default(false);
            $table->timestamp('terakhir_aktif')->nullable();

            $table->boolean('aktif')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('display_devices');
    }
};