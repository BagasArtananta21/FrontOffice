<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_counters_before_update');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER nomor_counters_before_update
            BEFORE UPDATE ON nomor_counters
            FOR EACH ROW
            BEGIN
                IF NOT (NEW.opd_id <=> OLD.opd_id)
                    OR NOT (NEW.jenis <=> OLD.jenis)
                    OR NOT (NEW.periode <=> OLD.periode) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kunci counter (OPD, jenis, periode) tidak boleh diubah';
                END IF;

                IF NEW.nomor_terakhir < OLD.nomor_terakhir THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor terakhir tidak boleh diturunkan';
                END IF;
            END
        SQL);

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER nomor_counters_before_delete
            BEFORE DELETE ON nomor_counters
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Counter penomoran tidak boleh dihapus';
            END
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_counters_before_delete');
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_counters_before_update');

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
    }
};
