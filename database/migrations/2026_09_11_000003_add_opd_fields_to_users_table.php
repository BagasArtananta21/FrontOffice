<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('opd_id')->nullable()->after('id')
                  ->constrained('opd')->restrictOnDelete();

            $table->string('nip', 30)->nullable()->unique()->after('name');
            $table->string('role', 20)->default('admin_fo')->after('password');
            $table->string('sso_subject_id')->nullable()->unique()->after('role');
            $table->boolean('aktif')->default(true)->after('sso_subject_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opd_id');
            $table->dropColumn(['nip', 'role', 'sso_subject_id', 'aktif']);
        });
    }
};