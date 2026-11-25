<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Support\SweetAlert;
use App\Models\DisplayDevice;
use App\Models\Kunjungan;
use App\Services\DisplayDeviceService;
use App\Services\KunjunganService;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use RuntimeException;

class DisplayStatus extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;
    
    protected string $view = 'filament.admin.widgets.display-status';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 1;

    #[Locked]
    public ?string $watchedKunjunganId = null;

    public function mount(): void
    {
        $this->watchedKunjunganId = $this->waitingKunjunganId();
    }

    public function cancelSignatureAction(): Action
    {
        $displayConnected = fn () => $this->devices()->contains(fn (DisplayDevice $device) => $device->isConnected());

        return Action::make('cancelSignature')
            ->label('Batalkan permintaan tanda tangan')
            ->color('danger')
            ->outlined()
            ->extraAttributes(['class' => 'w-full'])
            ->modalHeading('Batalkan Permintaan Tanda Tangan')
            ->modalDescription(fn () => $displayConnected()
                ? 'Kunjungan tetap tercatat tanpa tanda tangan. Pilih alasannya.'
                : 'Display terputus. Kunjungan tetap tercatat dengan alasan "Display tidak tersedia".')
            ->modalSubmitActionLabel('Batalkan Permintaan')
            ->schema([
                Select::make('reason')
                    ->label('Alasan')
                    ->options(Arr::only(Kunjungan::NO_SIGNATURE_REASONS, [Kunjungan::REASON_GUEST_LEFT, Kunjungan::REASON_GUEST_REFUSED]))
                    ->default(Kunjungan::REASON_GUEST_LEFT)
                    ->visible($displayConnected)
                    ->required($displayConnected),
            ])
            ->action(function (array $data) {
                $cancelled = app(KunjunganService::class)->cancelSignatureRequest($data['reason'] ?? null);
                $this->watchedKunjunganId = null;

                if (! $cancelled) {
                    SweetAlert::info(
                        $this,
                        'Tidak ada yang Dibatalkan',
                        'Permintaan tanda tangan sudah selesai atau sudah melewati batas waktu.'
                    );
                    return;
                }

                SweetAlert::info(
                    $this, 
                    'Permintaan Tanda Tangan Dibatalkan', 
                    'Kunjungan tetap tercatat tanpa tanda tangan. Display kembali ke formulir.'
                );
            });
    }

    public function showPinAction(): Action
    {
        return Action::make('showPin')
            ->label('Lihat PIN')
            ->icon('heroicon-o-key')
            ->modalHeading('PIN Display')
            ->modalDescription('Ketik PIN ini di halaman pemasangan pada PC display. Jangan bagikan ke orang lain.')
            ->modalContent(fn (): Htmlable => $this->pinContent())
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->visible(fn (): bool => $this->device() !== null);
    }

    public function reloadAction(): Action
    {
        return Action::make('reload')
            ->label('Muat ulang display')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->requiresConfirmation()
            ->modalHeading('Muat Ulang Display')
            ->modalDescription('Halaman di PC display akan dimuat ulang. Isian form yang sedang diketik tamu akan hilang.')
            ->modalSubmitActionLabel('Muat ulang')
            ->visible(fn (): bool => $this->device()?->isPaired() === true)
            ->action(function (): void {
                $device = $this->device();

                if ($device === null) {
                    return;
                }

                app(DisplayDeviceService::class)->requestReload($device);

                if (! $device->isConnected()) {
                    SweetAlert::warning(
                        $this,
                        'Display Tidak Terhubung',
                        'Display akan dimuat ulang begitu terhubung kembali.'
                    );
                    return;
                }

                SweetAlert::success(
                    $this,
                    'Display Dimuat Ulang',
                    'Halaman display akan dimuat ulang dalam beberapa detik.'
                );
            });
    }

    public function regeneratePinAction(): Action
    {
        return Action::make('regeneratePin')
            ->label('Ganti PIN')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->modalHeading('Ganti PIN Display')
            ->modalDescription('PIN lama tidak bisa dipakai lagi. Display yang sedang terpasang tetap berjalan.')
            ->modalSubmitActionLabel('Ganti PIN')
            ->visible(fn (): bool => $this->device() !== null)
            ->action(function (): void {
                $device = $this->device();

                if ($device === null) {
                    return;
                }

                try {
                    app(DisplayDeviceService::class)->assignNewPin($device);
                } catch (RuntimeException $e) {
                    report($e);
                    SweetAlert::error(
                        $this,
                        'Gagal Mengganti PIN',
                        'Terjadi kesalahan saat mengganti PIN. Silakan coba lagi.'
                    );
                    return;
                }

                SweetAlert::success(
                    $this,
                    'PIN Diganti',
                    'PIN baru sudah berlaku. Tekan Lihat PIN untuk melihatnya.'
                );
            });
    }

    public function unpairAction(): Action
    {
        return Action::make('unpair')
            ->label('Putuskan display')
            ->icon('heroicon-o-link-slash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Putuskan Display')
            ->modalDescription('PC display yang sekarang terpasang akan terputus. Pakai ini bila PC display diganti atau browser-nya dibersihkan, lalu masukkan PIN lagi di PC display.')
            ->modalSubmitActionLabel('Putuskan')
            ->visible(fn (): bool => $this->device()?->isPaired() === true)
            ->action(function (): void {
                $device = $this->device();

                if ($device === null) {
                    return;
                }

                try {
                    app(DisplayDeviceService::class)->unpair($device);
                } catch (InvalidArgumentException) {
                    SweetAlert::warning(
                        $this,
                        'Menunggu Tanda Tangan',
                        'Batalkan permintaan tanda tangan dulu sebelum memutuskan display.'
                    );
                    return;
                }

                SweetAlert::success(
                    $this,
                    'Display Diputuskan',
                    'Masukkan PIN di PC display untuk memasangnya lagi.'
                );
            });
    }

    private function pinContent(): Htmlable
    {
        $pin = $this->device()?->pin;

        if ($pin === null) {
            return new HtmlString('<p class="text-center text-sm text-gray-500">PIN belum dibuat. Tekan Ganti PIN untuk membuatnya.</p>');
        }

        return new HtmlString('<p class="text-center text-4xl font-bold tracking-[0.3em] tabular-nums text-gray-950">'.e($pin).'</p>');
    }

    public function checkSignatureRequest(): void
    {
        foreach ($this->devices() as $device) {
            if ($device->signatureRequestExpired()) {
                app(KunjunganService::class)->expireSignatureRequest($device);
            }
        }

        $current = $this->waitingKunjunganId();

        if ($this->watchedKunjunganId !== null && $this->watchedKunjunganId !== $current) {
            $kunjungan = Kunjungan::find($this->watchedKunjunganId);
            $minutes = DisplayDevice::signatureTimeoutMinutes();

            $message = match ($kunjungan?->alasan_tanpa_ttd) {
                Kunjungan::REASON_TIMEOUT => "{$kunjungan->nama_tamu} tidak menandatangani dalam {$minutes} menit.",
                Kunjungan::REASON_DISPLAY_UNAVAILABLE => "Display terputus lebih dari {$minutes} menit saat menunggu tanda tangan {$kunjungan->nama_tamu}.",
                default => null,
            };

            if ($message) {
                SweetAlert::info($this, 
                'Waktu Tanda Tangan Habis', 
                $message.' Kunjungan tetap tercatat tanpa tanda tangan.');
            }
        }

        $this->watchedKunjunganId = $current;
    }

    private function waitingKunjunganId(): ?string
    {
        return DisplayDevice::query()
            ->where('aktif', true)
            ->whereNotNull('kunjungan_menunggu_id')
            ->value('kunjungan_menunggu_id');
    }

    protected function devices(): Collection
    {
        return DisplayDevice::query()
            ->with('kunjunganMenunggu')
            ->where('aktif', true)
            ->get();
    }

    protected function device(): ?DisplayDevice
    {
        return $this->devices()->first();
    }

    protected function getViewData(): array
    {
        $devices = $this->devices();
        $waitingDevice = $devices->first(fn (DisplayDevice $device) => $device->kunjungan_menunggu_id !== null);

        return [
            'terdaftar' => $devices->isNotEmpty(),
            'terpasang' => $devices->first()?->isPaired() === true,
            'pairUrl' => route('display.pair'),
            'terhubung' => $devices->contains(fn (DisplayDevice $device) => $device->isConnected()),
            'menungguNamaTamu' => $waitingDevice?->kunjunganMenunggu?->nama_tamu,
        ];
    }

    public function clearFormAction(): Action
    {
        return Action::make('clearForm')
            ->label('Bersihkan formulir')
            ->icon('heroicon-o-trash')
            ->color('gray')
            ->outlined()
            ->extraAttributes(['class' => 'w-full'])
            ->requiresConfirmation()
            ->modalHeading('Bersihkan Formulir')
            ->modalDescription('Semua isian di form tamu pada display, termasuk tanda tangan, akan dikosongkan.')
            ->modalSubmitActionLabel('Bersihkan')
            ->visible(fn (): bool => $this->device()?->isPaired() === true)
            ->action(function (): void {
                $device = $this->device();

                if ($device === null) {
                    return;
                }

                app(DisplayDeviceService::class)->requestFormClear($device);

                if (! $device->isConnected()) {
                    SweetAlert::warning(
                        $this,
                        'Display Tidak Terhubung',
                        'Formulir akan dikosongkan begitu display terhubung kembali.'
                    );
                    return;
                }

                SweetAlert::success(
                    $this,
                    'Formulir Dibersihkan',
                    'Form di display akan kosong dalam beberapa detik.'
                );
            });
    }

}
