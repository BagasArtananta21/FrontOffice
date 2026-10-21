<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-2">

        <div class="rounded-xl border border-l-4 border-gray-200 border-l-primary bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nomor Terakhir {{ $year }}</p>
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <x-filament::icon icon="heroicon-o-hashtag" class="size-5" />
                </span>
            </div>

            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $lastNumber }}</p>
            <p class="mt-3 truncate text-xs text-gray-500">{{ $lastNote }}</p>
        </div>

        <div class="rounded-xl border border-l-4 border-gray-200 border-l-secondary bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $monthTitle }} · {{ $monthLabel }}</p>
                <span class="flex size-9 items-center justify-center rounded-lg bg-secondary/10 text-secondary">
                    <x-filament::icon icon="heroicon-o-document-text" class="size-5" />
                </span>
            </div>

            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900">{{ $issuedThisMonth }}</span>
                <span class="text-sm text-gray-500">Nomor</span>
            </p>
            <p class="mt-3 text-xs text-gray-500">{{ $cancelledThisMonth }} dibatalkan · {{ $basisNote }}</p>
        </div>
    </div>
</x-filament-widgets::widget>
