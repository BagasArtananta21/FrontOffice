<?php

namespace Tests\Feature\Exports;

use App\Exports\VisitHistoryExport;
use App\Models\Kunjungan;
use App\Models\Opd;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class VisitHistoryExportTest extends TestCase
{
    use RefreshDatabase;

    private function createVisit(array $attributes): Kunjungan
    {
        $opd = Opd::create(['kode_opd' => 'UJI', 'nama_opd' => 'OPD Uji']);
        app()->instance('current_opd_id', $opd->id);

        $kunjungan = new Kunjungan([
            'jenis_kelamin' => 'laki_laki',
            ...$attributes,
        ]);
        $kunjungan->forceFill([
            'waktu_datang' => now(),
            'sumber_input' => Kunjungan::SUMBER_DISPLAY,
        ])->save();

        return $kunjungan->refresh();
    }

    private function exportedSheetXml(): string
    {
        $response = app(VisitHistoryExport::class)->download(Kunjungan::query());

        ob_start();
        $response->sendContent();
        $binary = ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        file_put_contents($path, $binary);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'Hasil ekspor bukan berkas zip/xlsx yang valid.');
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        $this->assertIsString($xml, 'sheet1.xml tidak ditemukan di dalam berkas ekspor.');

        return $xml;
    }

    public function test_guest_text_starting_with_equals_is_exported_as_text_not_as_formula(): void
    {
        $payloads = [
            'nama_tamu' => '=1+1',
            'instansi_asal' => '=2+2',
            'alamat' => '=3+3',
            'pegawai_tujuan' => '=4+4',
            'keperluan' => '=5+5',
            'catatan_petugas' => '=6+6',
        ];

        $this->createVisit($payloads);

        $xml = $this->exportedSheetXml();

        $this->assertStringNotContainsString('<f>', $xml);

        foreach ($payloads as $payload) {
            $this->assertStringContainsString("<t>{$payload}</t>", $xml);
        }
    }

    public function test_regular_values_are_still_exported(): void
    {
        $kunjungan = $this->createVisit([
            'nama_tamu' => 'Budi Santoso',
            'keperluan' => 'Konsultasi',
        ]);

        $xml = $this->exportedSheetXml();

        $this->assertStringContainsString('<t>Budi Santoso</t>', $xml);
        $this->assertStringContainsString('<t>Laki-laki</t>', $xml);
        $this->assertStringContainsString("<t>{$kunjungan->nomor_kunjungan}</t>", $xml);
    }
}
