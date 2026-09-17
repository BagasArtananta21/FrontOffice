@php
    $user = filament()->auth()->user();
@endphp

<div class="hidden text-end leading-tight sm:block">
    <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
    <p class="text-xs text-gray-500">
        {{ $user->isSuperAdmin() ? 'Super Admin' : 'Admin FO' }}
        @if ($user->opd)
            — {{ $user->opd->kode_opd }}
        @endif
    </p>
</div>
