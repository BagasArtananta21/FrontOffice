<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER kunjungan_before_update
            BEFORE UPDATE ON kunjungan
            FOR EACH ROW
            BEGIN
                IF NOT (NEW.nomor_kunjungan <=> OLD.nomor_kunjungan)
                    OR NOT (NEW.opd_id <=> OLD.opd_id)
                    OR NOT (NEW.dicatat_oleh <=> OLD.dicatat_oleh)
                    OR NOT (NEW.sumber_input <=> OLD.sumber_input)
                    OR NOT (NEW.waktu_datang <=> OLD.waktu_datang)
                    OR (OLD.tanda_tangan IS NOT NULL AND NOT (NEW.tanda_tangan <=> OLD.tanda_tangan)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor kunjungan, OPD, petugas, sumber input, waktu datang, dan tanda tangan tidak boleh diubah';
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER kunjungan_before_delete
            BEFORE DELETE ON kunjungan
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data kunjungan tidak boleh dihapus';
            END
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS kunjungan_before_update');
        DB::unprepared('DROP TRIGGER IF EXISTS kunjungan_before_delete');
    }
};
