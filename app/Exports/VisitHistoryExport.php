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

class VisitHistoryExport
{
    // judul kolom => lebar (dalam karakter)
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

            $query->with(['bidang', 'pegawai', 'petugas'])
                ->orderBy('kunjungan.id')
                ->lazy(500)
                ->each(function (Kunjungan $kunjungan) use ($writer, $dateStyle, &$rowCount) {
                    $writer->addRow(Row::fromValuesWithStyles([
                        $kunjungan->nomor_kunjungan,
                        $kunjungan->waktu_datang,
                        $kunjungan->nama_tamu,
                        Kunjungan::JENIS_KELAMIN[$kunjungan->jenis_kelamin] ?? $kunjungan->jenis_kelamin,
                        $kunjungan->instansi_asal,
                        $kunjungan->no_hp,
                        $kunjungan->alamat,
                        $kunjungan->bidang?->nama_bidang,
                        $kunjungan->pegawai?->nama_pegawai,
                        $kunjungan->keperluan,
                        $kunjungan->catatan_petugas,
                        $kunjungan->sumber_input === Kunjungan::SUMBER_FRONT_OFFICE ? 'Front Office' : 'Display',
                        $kunjungan->petugas?->name,
                    ], null, [1 => $dateStyle]));

                    $rowCount++;
                });

            $sheet->setAutoFilter(new AutoFilter(0, 1, count(self::COLUMNS) - 1, $rowCount + 1));

            $writer->close();
        }, $fileName);
    }
}
