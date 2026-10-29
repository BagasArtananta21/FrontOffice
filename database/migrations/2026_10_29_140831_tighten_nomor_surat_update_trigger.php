<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_surat_before_update');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER nomor_surat_before_update
            BEFORE UPDATE ON nomor_surat
            FOR EACH ROW
            BEGIN
                IF NOT (NEW.opd_id <=> OLD.opd_id)
                    OR NOT (NEW.format_nomor_surat_id <=> OLD.format_nomor_surat_id)
                    OR NOT (NEW.bidang_id <=> OLD.bidang_id)
                    OR NOT (NEW.tahun <=> OLD.tahun)
                    OR NOT (NEW.nomor_urut <=> OLD.nomor_urut)
                    OR NOT (NEW.sub_nomor <=> OLD.sub_nomor)
                    OR NOT (NEW.kode_klasifikasi <=> OLD.kode_klasifikasi)
                    OR NOT (NEW.perihal <=> OLD.perihal)
                    OR NOT (NEW.tujuan_surat <=> OLD.tujuan_surat)
                    OR NOT (NEW.nama_peminta <=> OLD.nama_peminta)
                    OR NOT (NEW.tanggal_surat <=> OLD.tanggal_surat)
                    OR NOT (NEW.tanggal_terbit <=> OLD.tanggal_terbit)
                    OR NOT (NEW.dibuat_oleh <=> OLD.dibuat_oleh)
                    OR (OLD.nomor_lengkap IS NOT NULL AND NOT (NEW.nomor_lengkap <=> OLD.nomor_lengkap)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor surat tidak boleh diubah';
                END IF;

                IF OLD.status = 'batal' AND NEW.status <> 'batal' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor yang dibatalkan tidak bisa dipulihkan';
                END IF;

                IF NEW.status NOT IN ('terbit', 'batal') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Status nomor surat tidak dikenal';
                END IF;

                IF OLD.status = 'terbit' AND NEW.status = 'batal' AND (
                    NEW.alasan_batal IS NULL
                    OR NEW.dibatalkan_oleh IS NULL
                    OR NEW.batal_pada IS NULL
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data pembatalan harus lengkap saat membatalkan nomor';
                END IF;

                IF NEW.status = OLD.status AND (
                    NOT (NEW.alasan_batal <=> OLD.alasan_batal)
                    OR NOT (NEW.dibatalkan_oleh <=> OLD.dibatalkan_oleh)
                    OR NOT (NEW.batal_pada <=> OLD.batal_pada)
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data pembatalan hanya bisa diisi saat membatalkan';
                END IF;
            END
        SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS nomor_surat_before_update');

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER nomor_surat_before_update
            BEFORE UPDATE ON nomor_surat
            FOR EACH ROW
            BEGIN
                IF NOT (NEW.opd_id <=> OLD.opd_id)
                    OR NOT (NEW.format_nomor_surat_id <=> OLD.format_nomor_surat_id)
                    OR NOT (NEW.bidang_id <=> OLD.bidang_id)
                    OR NOT (NEW.tahun <=> OLD.tahun)
                    OR NOT (NEW.nomor_urut <=> OLD.nomor_urut)
                    OR NOT (NEW.sub_nomor <=> OLD.sub_nomor)
                    OR NOT (NEW.kode_klasifikasi <=> OLD.kode_klasifikasi)
                    OR NOT (NEW.perihal <=> OLD.perihal)
                    OR NOT (NEW.tujuan_surat <=> OLD.tujuan_surat)
                    OR NOT (NEW.nama_peminta <=> OLD.nama_peminta)
                    OR NOT (NEW.tanggal_surat <=> OLD.tanggal_surat)
                    OR NOT (NEW.tanggal_terbit <=> OLD.tanggal_terbit)
                    OR NOT (NEW.dibuat_oleh <=> OLD.dibuat_oleh)
                    OR (OLD.nomor_lengkap IS NOT NULL AND NOT (NEW.nomor_lengkap <=> OLD.nomor_lengkap)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor surat tidak boleh diubah';
                END IF;

                IF OLD.status = 'batal' AND NEW.status <> 'batal' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Nomor yang dibatalkan tidak bisa dipulihkan';
                END IF;

                IF NEW.status = OLD.status AND (
                    NOT (NEW.alasan_batal <=> OLD.alasan_batal)
                    OR NOT (NEW.dibatalkan_oleh <=> OLD.dibatalkan_oleh)
                    OR NOT (NEW.batal_pada <=> OLD.batal_pada)
                ) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data pembatalan hanya bisa diisi saat membatalkan';
                END IF;
            END
        SQL);
    }
};
