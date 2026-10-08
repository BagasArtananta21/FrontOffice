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

class ManageNomorSurats extends ManageRecords
{
    protected static string $resource = NomorSuratResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Nomor Surat')
                ->modalHeading('Terbitkan Nomor Surat')
                ->modalSubmitActionLabel('Terbitkan')
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
                ->after(fn ($livewire, NomorSurat $record) => SweetAlert::success(
                    $livewire,
                    'Nomor Surat Berhasil Diterbitkan',
                    $record->nomor_lengkap,
                ))
                ->createAnother(false),

            Action::make('setLastNumber')
                ->label('Atur Nomor Terakhir')
                ->color('gray')
                ->modalHeading('Atur Nomor Terakhir Tahun '.now()->year)
                ->modalDescription('Isi dengan nomor terakhir yang sudah terpakai di buku agenda. Nomor berikutnya dari sistem adalah angka ini ditambah satu. Angka ini tidak bisa diturunkan setelah disimpan.')
                ->modalSubmitActionLabel('Simpan')
                ->schema([
                    TextInput::make('nomor_terakhir')
                        ->label('Nomor Terakhir')
                        ->integer()
                        ->required()
                        ->minValue(fn () => self::currentLastNumber())
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
                    SweetAlert::success($livewire, 'Nomor Terakhir Disimpan', "Nomor surat berikutnya: {$berikutnya}");
                }),
        ];
    }

    private static function currentLastNumber(): int
    {
        return (int) NomorCounter::query()
            ->where('jenis', 'surat')
            ->where('periode', (string) now()->year)
            ->value('nomor_terakhir');
    }
}
