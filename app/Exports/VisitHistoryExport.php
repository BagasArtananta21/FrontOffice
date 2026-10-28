<?php

namespace App\Exports;

use App\Models\Kunjungan;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\Concerns\WritesTextCells;
use OpenSpout\Common\Entity\Cell;

class VisitHistoryExport
{
    use WritesTextCells;

    private const COLUMNS = [
        'Nomor Kunjungan' => 17,
        'Waktu Datang' => 17,
        'Nama Tamu' => 28,
        'Jenis Kelamin' => 14,
        'Instansi Asal' => 32,
        'Nomor HP' => 16,
        'Alamat' => 40,
        'Bidang' => 45,
        'Pegawai' => 32,
        'Keperluan' => 45,
        'Keterangan' => 40,
        'Sumber' => 14,
        'Petugas' => 28,
    ];

    public function download(Builder $query): StreamedResponse
    {
        $fileName = 'riwayat-kunjungan-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($query) {
            $options = new Options();
            $options->DEFAULT_ROW_STYLE->setShouldWrapText(false);

            foreach (array_values(self::COLUMNS) as $index => $width) {
                $options->setColumnWidth($width, $index + 1);
            }

            $writer = new Writer($options);
            $writer->openToFile('php://output');

            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Riwayat Kunjungan');
            $sheet->setSheetView((new SheetView())->setFreezeRow(2));

            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('0F2C59');

            $writer->addRow(Row::fromValues(array_keys(self::COLUMNS), $headerStyle));

            $dateStyle = (new Style())->setFormat('dd/mm/yyyy hh:mm');
            $rowCount = 0;

            $query->with(['bidang', 'petugas'])
                ->orderBy('kunjungan.id')
                ->lazy(500)
                ->each(function (Kunjungan $kunjungan) use ($writer, $dateStyle, &$rowCount) {
                    $writer->addRow(new Row([
                        $this->textCell($kunjungan->nomor_kunjungan),
                        Cell::fromValue($kunjungan->waktu_datang, $dateStyle),
                        $this->textCell($kunjungan->nama_tamu),
                        $this->textCell(Kunjungan::JENIS_KELAMIN[$kunjungan->jenis_kelamin] ?? $kunjungan->jenis_kelamin),
                        $this->textCell($kunjungan->instansi_asal),
                        $this->textCell($kunjungan->no_hp),
                        $this->textCell($kunjungan->alamat),
                        $this->textCell($kunjungan->bidang?->nama_bidang),
                        $this->textCell($kunjungan->pegawai_tujuan),
                        $this->textCell($kunjungan->keperluan),
                        $this->textCell($kunjungan->catatan_petugas),
                        $this->textCell($kunjungan->sumber_input === Kunjungan::SUMBER_FRONT_OFFICE ? 'Front Office' : 'Display'),
                        $this->textCell($kunjungan->petugas?->name),
                    ]));

                    $rowCount++;
                });


            $sheet->setAutoFilter(new AutoFilter(0, 1, count(self::COLUMNS) - 1, $rowCount + 1));

            $writer->close();
        }, $fileName);
    }
}
