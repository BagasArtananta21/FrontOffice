<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER nomor_surat_before_insert
            BEFORE INSERT ON nomor_surat
            FOR EACH ROW
            BEGIN
                DECLARE v_nomor INT UNSIGNED;

                IF NEW.nomor_urut = 0 THEN
                    INSERT INTO nomor_counters (id, opd_id, jenis, periode, nomor_terakhir, created_at, updated_at)
                    VALUES (UUID(), NEW.opd_id, 'surat', CONCAT(NEW.tahun), 1, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE nomor_terakhir = nomor_terakhir + 1, updated_at = NOW();

                    SELECT nomor_terakhir INTO v_nomor
                    FROM nomor_counters
                    WHERE opd_id = NEW.opd_id AND jenis = 'surat' AND periode = CONCAT(NEW.tahun);

                    SET NEW.nomor_urut = v_nomor;
                    SET NEW.sub_nomor = 0;
                ELSE
                    SELECT nomor_terakhir INTO v_nomor
                    FROM nomor_counters
                    WHERE opd_id = NEW.opd_id AND jenis = 'surat' AND periode = CONCAT(NEW.tahun)
                    FOR UPDATE;

                    IF NOT EXISTS (
                        SELECT 1 FROM nomor_surat
                        WHERE opd_id = NEW.opd_id AND tahun = NEW.tahun
                        AND nomor_urut = NEW.nomor_urut AND sub_nomor = 0
                    ) THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor induk susulan tidak ditemukan';
                    END IF;

                    SELECT MAX(sub_nomor) + 1 INTO v_nomor
                    FROM nomor_surat
                    WHERE opd_id = NEW.opd_id AND tahun = NEW.tahun AND nomor_urut = NEW.nomor_urut;

                    SET NEW.sub_nomor = v_nomor;
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER nomor_counters_before_update
            BEFORE UPDATE ON nomor_counters
            FOR EACH ROW
            BEGIN
                IF NEW.nomor_terakhir < OLD.nomor_terakhir THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor terakhir tidak boleh diturunkan';
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER nomor_surat_before_update
            BEFORE UPDATE ON nomor_surat
            FOR EACH ROW
            BEGIN
                IF NEW.tahun <> OLD.tahun OR NEW.nomor_urut <> OLD.nomor_urut OR NEW.sub_nomor <> OLD.sub_nomor
                    OR (OLD.nomor_lengkap IS NOT NULL AND NOT (NEW.nomor_lengkap <=> OLD.nomor_lengkap)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor surat tidak boleh diubah';
                END IF;

                IF OLD.status = 'batal' AND NEW.status <> 'batal' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor yang dibatalkan tidak bisa dipulihkan';
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER nomor_surat_before_delete
            BEFORE DELETE ON nomor_surat
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor surat tidak boleh dihapus, gunakan pembatalan';
            END
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS kunjungan_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_surat_before_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_counters_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_surat_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_surat_before_delete');
    }

};
