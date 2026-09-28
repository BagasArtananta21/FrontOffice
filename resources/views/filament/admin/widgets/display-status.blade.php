<x-filament-widgets::widget wire:poll.5s>
    <x-filament::section>
        <div class="flex w-full items-start justify-between gap-4">
            <div>
                @if (! $terdaftar)
                    <p class="text-sm text-gray-500">Belum ada perangkat display aktif untuk OPD ini.</p>
                @else
                    <div class="flex items-center gap-2">
                        <span @class([
                            'h-2.5 w-2.5 rounded-full',
                            'bg-success-500' => $terhubung,
                            'bg-gray-300' => ! $terhubung,
                        ])></span>
                        <span class="text-sm font-medium">{{ $terhubung ? 'Terhubung' : 'Tidak terhubung' }}</span>
                    </div>

                    <p class="mt-1 text-sm text-gray-500">
                        Tampilan: <span class="font-medium text-gray-950">{{ $menampilkanForm ? 'Form tamu' : 'Layar idle' }}</span>
                    </p>
                @endif
            </div>

            <span class="ms-auto shrink-0 text-sm text-gray-500">Display Lobi</span>
        </div>

        @if ($terdaftar)
            <x-filament::button
                wire:click="setDisplayForm({{ $menampilkanForm ? 'false' : 'true' }})"
                :color="$menampilkanForm ? 'gray' : 'primary'"
                class="mt-4 w-full"
            >
                {{ $menampilkanForm ? 'Kembali ke layar idle' : 'Tampilkan form tamu' }}
            </x-filament::button>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
