<x-filament-widgets::widget>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {{-- Total tamu --}}
        <div class="rounded-xl border border-l-4 border-gray-200 border-l-[#1E3A8A] bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Tamu</p>
                <span class="flex size-9 items-center justify-center rounded-lg bg-[#1E3A8A]/10 text-[#1E3A8A]">
                    <x-filament::icon icon="heroicon-o-user-group" class="size-5" />
                </span>
            </div>

            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900">{{ $total }}</span>
                <span class="text-sm text-gray-500">Orang</span>
            </p>

            @if ($change === null)
                <p class="mt-3 text-xs text-gray-500">Tidak ada data periode sebelumnya</p>
            @else
                <span @class([
                    'mt-3 inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium',
                    'bg-[#16A34A]/10 text-[#16A34A]' => $change >= 0,
                    'bg-[#DC2626]/10 text-[#DC2626]' => $change < 0,
                ])>
                    <x-filament::icon
                        :icon="$change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'"
                        class="size-3.5"
                    />
                    {{ $change >= 0 ? '+' : '' }}{{ $change }}% dari periode lalu
                </span>
            @endif
        </div>

        {{-- Rata-rata per hari kerja --}}
        <div class="rounded-xl border border-l-4 border-gray-200 border-l-secondary bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rata-rata / Hari Kerja</p>
                <span class="flex size-9 items-center justify-center rounded-lg bg-secondary/10 text-secondary">
                    <x-filament::icon icon="heroicon-o-chart-bar" class="size-5" />
                </span>
            </div>

            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900">{{ $average }}</span>
                <span class="text-sm text-gray-500">Tamu / hari</span>
            </p>

            <p class="mt-3 text-xs text-gray-500">Total {{ $workdays }} hari kerja</p>
        </div>

        {{-- Mengisi sendiri di display --}}
        <div class="rounded-xl border border-l-4 border-gray-200 border-l-success bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mengisi Sendiri di Display</p>
                <span class="flex size-9 items-center justify-center rounded-lg bg-success/10 text-success">
                    <x-filament::icon icon="heroicon-o-computer-desktop" class="size-5" />
                </span>
            </div>

            <p class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-gray-900">{{ $displayShare === null ? '-' : $displayShare.'%' }}</span>
                <span class="text-sm text-success">({{ $fromDisplay }} / {{ $total }} Tamu)</span>
            </p>

            <div class="mt-3 h-1.5 rounded-full bg-gray-100">
                <div class="h-1.5 rounded-full bg-success" style="width: {{ $displayShare ?? 0 }}%"></div>
            </div>
        </div>

        {{-- Bidang terbanyak --}}
        <div class="rounded-xl border border-l-4 border-gray-200 border-l-primary bg-white p-5">
            <div class="flex items-start justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Bidang Terbanyak</p>
                <span class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <x-filament::icon icon="heroicon-o-building-office" class="size-5" />
                </span>
            </div>

            @if ($topBidang)
                <p class="mt-2 flex items-baseline gap-2">
                    <span class="text-3xl font-bold text-gray-900">{{ $topBidang->shortLabel() }}</span>
                    <span class="text-sm font-medium text-[#1E3A8A]">{{ $topBidangTotal }} Kunjungan</span>
                </p>

                <p class="mt-3 truncate text-xs text-gray-500">{{ $topBidang->nama_bidang }}</p>
            @else
                <p class="mt-2 text-3xl font-bold text-gray-900">-</p>
                <p class="mt-3 text-xs text-gray-500">Belum ada data</p>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
