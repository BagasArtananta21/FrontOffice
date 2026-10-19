<x-filament-widgets::widget>
    <x-filament::section heading="Perlu Perhatian" description="Kondisi saat ini, tidak mengikuti filter tanggal">
        @if ($items->isNotEmpty())
            <x-slot name="afterHeader">
                <span class="rounded-md bg-warning-500/10 px-2 py-0.5 text-xs font-medium text-warning-700">
                    {{ $items->count() }} hal
                </span>
            </x-slot>
        @endif

        @if ($items->isEmpty())
            <p class="flex items-center gap-2 text-sm text-success-700">
                <x-filament::icon icon="heroicon-o-check-circle" class="size-5" />
                Semua OPD dalam kondisi baik.
            </p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($items as $item)
                    <li class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <p class="flex min-w-0 items-start gap-2 text-sm text-gray-700">
                            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-warning-500" />
                            <span>{{ $item['message'] }}</span>
                        </p>

                        <a href="{{ $item['url'] }}" class="shrink-0 text-sm font-medium text-tertiary hover:underline">
                            Tindak lanjuti →
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
