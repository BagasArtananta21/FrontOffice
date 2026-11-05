<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomor_surat', function (Blueprint $table) {
            $table->index(['opd_id', 'tanggal_surat']);
        });
    }

    public function down(): void
    {
        Schema::table('nomor_surat', function (Blueprint $table) {
            $table->dropIndex(['opd_id', 'tanggal_surat']);
        });
    }
};
