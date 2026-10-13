@php
    $user = filament()->auth()->user();
@endphp

<div class="hidden items-center gap-4 sm:flex">
    <x-clock class="text-sm font-medium tabular-nums text-gray-500" />

    <span class="h-8 w-px bg-gray-200"></span>

    <div class="text-end leading-tight">
        <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
        <p class="text-xs text-gray-500">
            {{ $user->isSuperAdmin() ? 'Super Admin' : 'Admin FO' }}
            @if ($user->opd)
                — {{ $user->opd->kode_opd }}
            @endif
        </p>
    </div>
</div>
