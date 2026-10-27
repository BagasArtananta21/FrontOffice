<x-filament-widgets::widget wire:poll.5s="checkSignatureRequest">
    @php($active = $terdaftar && $terhubung)

    <div @class([
        'flex h-full flex-col rounded-xl border border-l-4 border-gray-200 bg-white p-5',
        'border-l-success' => $active,
        'border-l-gray-300' => ! $active,
    ])>
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Display Lobi</p>
            <span @class([
                'flex size-9 items-center justify-center rounded-lg',
                'bg-success/10 text-success' => $active,
                'bg-gray-100 text-gray-500' => ! $active,
            ])>
                <x-filament::icon icon="heroicon-o-computer-desktop" class="size-5" />
            </span>
        </div>

        @if (! $terdaftar)
            <p class="mt-2 text-3xl font-bold text-gray-900">-</p>
            <p class="mt-auto pt-3 text-xs text-gray-500">Belum ada perangkat display aktif untuk OPD ini</p>
        @else
            <p class="mt-2 flex h-9 items-center gap-2">
                <span @class([
                    'size-2.5 shrink-0 rounded-full',
                    'bg-success' => $terhubung,
                    'bg-gray-300' => ! $terhubung,
                ])></span>
                <span class="text-2xl font-bold text-gray-900">{{ $terhubung ? 'Terhubung' : 'Tidak terhubung' }}</span>
            </p>

            <p class="mt-3 line-clamp-1 text-xs text-gray-500">
                Tampilan:
                <span class="font-medium text-gray-900">
                    @if ($menungguNamaTamu)
                        Menunggu tanda tangan {{ $menungguNamaTamu }}
                    @elseif ($menampilkanForm)
                        Form tamu
                    @else
                        Layar idle
                    @endif
                </span>
            </p>

            <div class="mt-auto pt-4">
                @if ($menungguNamaTamu)
                    {{ $this->cancelSignatureAction }}
                @else
                    <x-filament::button
                        wire:click="setDisplayForm({{ $menampilkanForm ? 'false' : 'true' }})"
                        :color="$menampilkanForm ? 'gray' : 'primary'"
                        class="w-full"
                    >
                        {{ $menampilkanForm ? 'Kembali ke layar idle' : 'Tampilkan form tamu' }}
                    </x-filament::button>
                @endif
            </div>
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
