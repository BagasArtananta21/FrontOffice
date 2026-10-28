<?php

namespace App\Filament\Admin\Resources\NomorSurats\Pages;

use App\Exceptions\LetterBaseNumberNotFoundException;
use App\Exceptions\LetterFormatNotFoundException;
use App\Models\NomorSurat;
use App\Models\NomorCounter;
use App\Filament\Admin\Resources\NomorSurats\NomorSuratResource;
use App\Filament\Support\SweetAlert;
use App\Services\LetterNumberService;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\QueryException;
use Filament\Support\Enums\Width;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use App\Exports\LetterNumberExport;
use App\Filament\Admin\Widgets\LetterNumberStats;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ManageNomorSurats extends ManageRecords
{
    use ExposesTableToWidgets;

    protected function getHeaderWidgets(): array
    {
        return [
            LetterNumberStats::class,
        ];
    }

    protected static string $resource = NomorSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Nomor Surat')
                ->modalHeading('Terbitkan Nomor Surat')
                ->modalSubmitActionLabel('Terbitkan')
                ->steps(NomorSuratResource::issueSteps())
                ->modalWidth(Width::FiveExtraLarge)
                ->successNotification(null)
                ->using (function (array $data, $livewire, CreateAction $action){
                    try {
                        return app(LetterNumberService::class)->issue($data, Auth::user());

                    } catch (LetterBaseNumberNotFoundException) {
                        SweetAlert::warning(
                            $livewire,
                            'Tanggal Sebelum Sistem Dipakai',
                            'Belum ada nomor surat yang terbit pada atau sebelum tanggal itu di tahun yang sama, sehingga nomor susulan belum bisa dibuat. '
                        );
                        $action->halt();

                    } catch (LetterFormatNotFoundException) {
                        SweetAlert::warning(
                            $livewire,
                            'Format Nomor Surat Tidak Ditemukan',
                            'Belum ada format nomor surat yang berlaku untuk OPD ini. Silahkan menghubungi super admin.'
                        );
                        $action->halt();    

                    } catch (QueryException $e){
                        report($e);
                        SweetAlert::error(
                            $livewire,
                            'Gagal Menerbitkan Nomor Surat',
                            'Terjadi Kesalahan, Silahkan Coba Lagi.'
                        );
                        $action->halt();
                    }
                })
                ->after(function ($livewire, NomorSurat $record) {
                    $livewire->dispatch('nomor-surat-updated');

                    SweetAlert::success(
                        $livewire,
                        'Nomor Surat Berhasil Diterbitkan',
                        $record->nomor_lengkap);
                })
                ->createAnother(false),

            Action::make('export')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('success')
                ->outlined()
                ->action(function () {
                    $query = $this->getFilteredTableQuery();

                    if(! $query->clone()->exists()){
                        SweetAlert::warning(
                            $this,
                            'Tidak ada data untuk diekspor',
                            'Tidak ada nomor surat yang sesuai dengan filter yang diterapkan.'
                        );

                        return;
                    }

                    return app(LetterNumberExport::class)->download($query, $this->exportFileName());
                }),
            
            ActionGroup::make([
                Action::make('setLastNumber')
                    ->label('Atur Nomor Terakhir')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->color('warning')
                    ->modalHeading('Atur Nomor Terakhir Tahun '.now()->year)
                    ->modalDescription('Isi dengan nomor terakhir yang sudah terpakai di buku agenda. Nomor berikutnya dari sistem adalah angka ini ditambah satu. Angka ini tidak bisa diturunkan setelah disimpan.')
                    ->modalSubmitActionLabel('Simpan')
                    ->schema([
                        TextInput::make('nomor_terakhir')
                            ->label('Nomor Terakhir')
                            ->integer()
                            ->required()
                            ->minValue(fn () => self::currentLastNumber())
                            ->maxValue(LetterNumberService::MAX_LAST_NUMBER)
                            ->helperText(fn () => 'Saat ini: '.self::currentLastNumber()),
                    ])
                    ->action(function (array $data, $livewire, Action $action) {
                        try {
                            app(LetterNumberService::class)->setLastNumber(Auth::user()->opd_id, (int) $data['nomor_terakhir']);
                        } catch (QueryException $e) {
                            if ($e->getCode() === '45000') {
                                SweetAlert::warning(
                                    $livewire, 
                                    'Nomor Tidak Boleh Diturunkan', 
                                    'Nomor terakhir sudah lebih besar dari angka yang diisi.');
                            } else {
                                report($e);
                                SweetAlert::error(
                                    $livewire, 
                                    'Gagal Menyimpan', 
                                    'Terjadi kesalahan. Silakan coba lagi.');
                            }
                            $action->halt();
                        }
    
                        $berikutnya = (int) $data['nomor_terakhir'] + 1;
                        $livewire->dispatch('nomor-surat-updated');
                        SweetAlert::success($livewire, 'Nomor Terakhir Disimpan', "Nomor surat berikutnya: {$berikutnya}");
                    }),
            ])
            ->icon(Heroicon::EllipsisHorizontal)
            ->color('gray')
            ->tooltip('Aksi Lainnya'),
        ];
    }

    private static function currentLastNumber(): int
    {
        return (int) NomorCounter::query()
            ->where('jenis', 'surat')
            ->where('periode', (string) now()->year)
            ->value('nomor_terakhir');
    }

    private function exportFileName(): string
    {
        $range = $this->getTableFilterState('rentang_tanggal') ?? [];
        $from = $range['from'] ?? null;
        $until = $range['until'] ?? null;

        if(blank($from) && blank($until)){
            return 'nomor-surat-semua-'.now()->format('Ymd').'.xlsx';
        }

        $basis = ($range['basis'] ?? 'surat') === 'terbit' ? 'tgl-terbit' : 'tgl-surat';
        $start = filled($from) ? Carbon::parse($from)->toDateString() : 'awal';
        $end = filled($until) ? Carbon::parse($until)->toDateString() : now()->toDateString();

        return "nomor-surat-{$basis}-{$start}_{$end}.xlsx";
    }
}
