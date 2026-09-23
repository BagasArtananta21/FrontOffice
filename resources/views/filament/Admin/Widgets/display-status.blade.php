<x-filament-widgets::widget wire:poll.10s>
    <x-filament::section>
        <x-slot name="heading">Display Lobi</x-slot>

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

            <p class="mt-2 text-sm text-gray-500">
                Tampilan: <span class="font-medium text-gray-950">{{ $menampilkanForm ? 'Form tamu' : 'Layar idle' }}</span>
            </p>

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
