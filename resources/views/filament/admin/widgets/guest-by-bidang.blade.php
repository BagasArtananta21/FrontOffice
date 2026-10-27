<x-filament-widgets::widget>
    <x-filament::section heading="Kunjungan Per Bidang" description="Sebaran bidang yang dituju tamu">
        <x-slot name="afterHeader">
            <span class="rounded-md bg-tertiary/10 px-2 py-0.5 text-xs font-medium text-tertiary">
                {{ $bidangCount }} Bidang
            </span>
        </x-slot>

        @if ($rows->isEmpty())
            <p class="text-sm text-gray-500">Belum ada kunjungan pada rentang ini.</p>
        @else
            <ul class="space-y-4">
                @foreach ($rows as $row)
                    <li>
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <p class="min-w-0 truncate">
                                <span class="font-semibold text-gray-900">{{ $row['kode'] }}</span>
                                @if ($row['nama'])
                                    <span class="text-gray-500">· {{ $row['nama'] }}</span>
                                @endif
                            </p>
                            <p class="shrink-0 font-semibold text-gray-900">
                                {{ $row['total'] }}
                                <span class="font-normal text-gray-500">({{ $row['share'] }}%)</span>
                            </p>
                        </div>

                        <div class="mt-1.5 h-2 rounded-full bg-gray-100">
                            <div class="h-2 rounded-full bg-tertiary" style="width: {{ $row['width'] }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
