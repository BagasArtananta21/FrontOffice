<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $card)
            <div @class(['rounded-xl border border-l-4 border-gray-200 bg-white p-5', $card['border']])>
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $card['label'] }}</p>
                    <span @class(['flex size-9 items-center justify-center rounded-lg', $card['badge']])>
                        <x-filament::icon :icon="$card['icon']" class="size-5" />
                    </span>
                </div>

                <p class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-gray-900">{{ $card['value'] }}</span>
                    @if ($card['unit'])
                        <span class="text-sm text-gray-500">{{ $card['unit'] }}</span>
                    @endif
                </p>

                @isset($card['progress'])
                    <div class="mt-3 h-1.5 rounded-full bg-gray-100">
                        <div class="h-1.5 rounded-full bg-success-600" style="width: {{ $card['progress'] }}%"></div>
                    </div>
                @endisset

                @if ($card['note'])
                    <p class="mt-3 text-xs text-gray-500">{{ $card['note'] }}</p>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>

