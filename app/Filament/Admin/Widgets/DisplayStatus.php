<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Support\SweetAlert;
use App\Models\DisplayDevice;
use App\Models\Kunjungan;
use App\Services\KunjunganService;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Illuminate\Support\Arr;


class DisplayStatus extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions, InteractsWithSchemas;
    
    protected string $view = 'filament.admin.widgets.display-status';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 1;

    public ?string $watchedKunjunganId = null;

    public function mount(): void
    {
        $this->watchedKunjunganId = $this->waitingKunjunganId();
    }

    public function setDisplayForm(bool $tampilkan): void{
        $devices = $this->devices();

        if ($devices->isEmpty()) {
            SweetAlert::warning(
                $this, 
                'Display Belum Terdaftar', 
                'Belum ada perangkat display aktif untuk OPD ini'
            );
            return;
        }

        if ($devices->contains(fn (DisplayDevice $device) => $device->kunjungan_menunggu_id !== null)) {
            SweetAlert::warning(
                $this,
                'Menunggu Tanda Tangan',
                'Batalkan permintaan tanda tangan dulu sebelum mengubah tampilan display.'
            );
            return;
        }

        DisplayDevice::query()->where('aktif', true)->update(
            $tampilkan
                ? ['tampilkan_form' => true, 'ditampilkan_oleh' => Auth::id()]
                : ['tampilkan_form' => false],
        );


        if (! $tampilkan) {
            SweetAlert::info($this, 'Display Kembali ke Layar Idle');
            return;
        }

        if (! $devices->contains(fn (DisplayDevice $device) => $device->isConnected())) {
            SweetAlert::warning(
                $this, 
                'Display Tidak Terhubung', 
                'Form akan tampil begitu display terhubung kembali'
            );
            return;
        }

        SweetAlert::success(
            $this, 
            'Form Tamu Ditampilkan', 
            'Form tamu akan tampil di layar display dalam beberapa detik'
        );
    }

    public function cancelSignatureAction(): Action
    {
        $displayConnected = fn () => $this->devices()->contains(fn (DisplayDevice $device) => $device->isConnected());

        return Action::make('cancelSignature')
            ->label('Batalkan permintaan tanda tangan')
            ->color('danger')
            ->outlined()
            ->extraAttributes(['class' => 'mt-4 w-full'])
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
                app(KunjunganService::class)->cancelSignatureRequest($data['reason'] ?? null);

                SweetAlert::info($this, 'Permintaan Tanda Tangan Dibatalkan', 'Kunjungan tetap tercatat tanpa tanda tangan. Display kembali ke layar idle.');
            });
    }

    public function checkSignatureRequest(): void
    {
        $current = $this->waitingKunjunganId();

        if ($this->watchedKunjunganId !== null && $this->watchedKunjunganId !== $current) {
            $kunjungan = Kunjungan::find($this->watchedKunjunganId);

            if ($kunjungan?->alasan_tanpa_ttd === Kunjungan::REASON_TIMEOUT) {
                SweetAlert::warning(
                    $this,
                    'Waktu Tanda Tangan Habis',
                    "{$kunjungan->nama_tamu} tidak menandatangani dalam ".DisplayDevice::SIGNATURE_TIMEOUT_MINUTES.' menit. Kunjungan tetap tercatat tanpa tanda tangan.'
                );
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
        return DisplayDevice::query()->where('aktif', true)->get();
    }

    protected function getViewData(): array
    {
        $devices = $this->devices();
        $waitingDevice = $devices->first(fn (DisplayDevice $device) => $device->kunjungan_menunggu_id !== null);

        return [
            'terdaftar' => $devices->isNotEmpty(),
            'terhubung' => $devices->contains(fn (DisplayDevice $device) => $device->isConnected()),
            'menampilkanForm' => $devices->first()?->tampilkan_form === true,
            'menungguNamaTamu' => $waitingDevice?->kunjunganMenunggu?->nama_tamu,
        ];
    }
}
