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
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER kunjungan_before_insert
            BEFORE INSERT ON kunjungan
            FOR EACH ROW
            BEGIN
                DECLARE v_nomor INT UNSIGNED;

                INSERT INTO nomor_counters (id, opd_id, jenis, periode, nomor_terakhir, created_at, updated_at)
                VALUES (UUID(), NEW.opd_id, 'kunjungan', DATE_FORMAT(NEW.waktu_datang, '%Y%m'), 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE nomor_terakhir = nomor_terakhir + 1, updated_at = NOW();

                SELECT nomor_terakhir INTO v_nomor
                FROM nomor_counters
                WHERE opd_id = NEW.opd_id
                AND jenis = 'kunjungan'
                AND periode = DATE_FORMAT(NEW.waktu_datang, '%Y%m');

                IF v_nomor > 9999 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kapasitas nomor kunjungan bulan ini habis';
                END IF;

                SET NEW.nomor_kunjungan = CONCAT(DATE_FORMAT(NEW.waktu_datang, '%Y%m%d'), '-', LPAD(v_nomor, 4, '0'));
            END
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS kunjungan_before_insert');
    }

};
