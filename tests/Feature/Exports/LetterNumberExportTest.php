<?php

namespace Tests\Feature\Exports;

use App\Exports\LetterNumberExport;
use App\Models\Bidang;
use App\Models\FormatNomorSurat;
use App\Models\NomorSurat;
use App\Models\Opd;
use App\Models\User;
use App\Services\LetterNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class LetterNumberExportTest extends TestCase
{
    use RefreshDatabase;

    private User $officer;

    private NomorSurat $letter;

    protected function setUp(): void
    {
        parent::setUp();

        $opd = Opd::create(['kode_opd' => 'UJI', 'nama_opd' => 'OPD Uji']);
        app()->instance('current_opd_id', $opd->id);

        $this->officer = new User();
        $this->officer->forceFill([
            'name' => '=6+6',
            'email' => 'petugas@example.com',
            'password' => 'abcdefghijkl',
            'role' => 'admin_fo',
            'opd_id' => $opd->id,
            'aktif' => true,
        ])->save();

        $format = new FormatNomorSurat();
        $format->forceFill([
            'susunan' => [
                ['jenis' => 'klasifikasi'],
                ['jenis' => 'urut'],
                ['jenis' => 'kode_opd'],
                ['jenis' => 'bulan_romawi'],
                ['jenis' => 'tahun'],
            ],
            'berlaku_sejak' => now()->startOfYear()->toDateString(),
            'izinkan_susulan' => false,
            'dibuat_oleh' => $this->officer->id,
        ])->save();

        $bidang = new Bidang();
        $bidang->forceFill(['nama_bidang' => '=5+5', 'kode_bidang' => 'B1', 'aktif' => true])->save();

        $service = app(LetterNumberService::class);

        $this->letter = $service->issue([
            'kode_klasifikasi' => '443.32',
            'bidang_id' => $bidang->id,
            'perihal' => '=1+1',
            'tujuan_surat' => '=2+2',
            'nama_peminta' => '=3+3',
            'tanggal_surat' => today()->toDateString(),
        ], $this->officer);

        $service->cancel($this->letter, '=4+4', $this->officer);
    }

    private function exportedSheetXml(): string
    {
        $response = app(LetterNumberExport::class)->download(NomorSurat::query(), 'uji.xlsx');

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

    public function test_text_starting_with_equals_is_exported_as_text_not_as_formula(): void
    {
        $xml = $this->exportedSheetXml();

        $this->assertStringNotContainsString('<f>', $xml);

        foreach (['=1+1', '=2+2', '=3+3', '=4+4', '=5+5', '=6+6'] as $payload) {
            $this->assertStringContainsString("<t>{$payload}</t>", $xml);
        }
    }

    public function test_regular_values_are_still_exported(): void
    {
        $xml = $this->exportedSheetXml();

        $this->assertStringContainsString("<t>{$this->letter->refresh()->nomor_lengkap}</t>", $xml);
        $this->assertStringContainsString('<t>Dibatalkan</t>', $xml);
    }
}
