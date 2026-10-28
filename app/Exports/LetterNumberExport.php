<?php

namespace App\Exports;

use App\Models\NomorSurat;
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

class LetterNumberExport
{
    use WritesTextCells;

    private const COLUMNS = [
        'Nomor Surat' => 40,
        'Tanggal Surat' => 14,
        'Tanggal Terbit' => 17,
        'Perihal' => 50,
        'Tujuan' => 32,
        'Bidang' => 40,
        'Peminta' => 28,
        'Status' => 12,
        'Alasan Batal' => 40,
        'Diterbitkan Oleh' => 28,
    ];

    public function download(Builder $query, string $fileName): StreamedResponse
    {
        return response()->streamDownload(function () use ($query) {
            $options = new Options();
            $options->DEFAULT_ROW_STYLE->setShouldWrapText(false);

            foreach (array_values(self::COLUMNS) as $index => $width) {
                $options->setColumnWidth($width, $index + 1);
            }

            $writer = new Writer($options);
            $writer->openToFile('php://output');

            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Nomor Surat');
            $sheet->setSheetView((new SheetView())->setFreezeRow(2));

            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('0F2C59');

            $writer->addRow(Row::fromValues(array_keys(self::COLUMNS), $headerStyle));

            $dateStyle = (new Style())->setFormat('dd/mm/yyyy');
            $dateTimeStyle = (new Style())->setFormat('dd/mm/yyyy hh:mm');
            $rowCount = 0;

            $query->with(['bidang', 'pembuat'])
                ->reorder()
                ->orderBy('tahun')
                ->orderBy('nomor_urut')
                ->orderBy('sub_nomor')
                ->lazy(500)
                ->each(function (NomorSurat $nomorSurat) use ($writer, $dateStyle, $dateTimeStyle, &$rowCount) {
                    $writer->addRow(new Row([
                        $this->textCell($nomorSurat->nomor_lengkap),
                        Cell::fromValue($nomorSurat->tanggal_surat, $dateStyle),
                        Cell::fromValue($nomorSurat->tanggal_terbit, $dateTimeStyle),
                        $this->textCell($nomorSurat->perihal),
                        $this->textCell($nomorSurat->tujuan_surat),
                        $this->textCell($nomorSurat->bidang?->nama_bidang),
                        $this->textCell($nomorSurat->nama_peminta),
                        $this->textCell($nomorSurat->status === NomorSurat::STATUS_BATAL ? 'Dibatalkan' : 'Terbit'),
                        $this->textCell($nomorSurat->alasan_batal),
                        $this->textCell($nomorSurat->pembuat?->name),
                    ]));

                    $rowCount++;
                });


            $sheet->setAutoFilter(new AutoFilter(0, 1, count(self::COLUMNS) - 1, $rowCount + 1));

            $writer->close();
        }, $fileName);
    }
}
