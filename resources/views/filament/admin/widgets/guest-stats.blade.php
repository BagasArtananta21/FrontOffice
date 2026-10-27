<x-filament-widgets::widget wire:poll.10s>
    <div class="grid h-full gap-4 md:grid-cols-2">
        <div class="flex h-full flex-col rounded-xl border border-l-4 border-gray-200 border-l-primary bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tamu Hari Ini</p>
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <x-filament::icon icon="heroicon-o-user-group" class="size-5" />
                </span>
            </div>

            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900">{{ $today }}</span>
                <span class="text-sm text-gray-500">Orang</span>
            </p>
            <p class="mt-auto pt-3 text-xs text-gray-500">{{ $todayLabel }}</p>
        </div>

        <div class="flex h-full flex-col rounded-xl border border-l-4 border-gray-200 border-l-secondary bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tamu Bulan Ini</p>
                <span class="flex size-9 items-center justify-center rounded-lg bg-secondary/10 text-secondary">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="size-5" />
                </span>
            </div>

            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900">{{ $month }}</span>
                <span class="text-sm text-gray-500">Orang</span>
            </p>
            <p class="mt-auto pt-3 text-xs text-gray-500">{{ $monthLabel }}</p>
        </div>
    </div>
</x-filament-widgets::widget>
