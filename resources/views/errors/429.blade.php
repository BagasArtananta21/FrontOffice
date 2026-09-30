@php
    $seconds = (int) ($exception->getHeaders()['Retry-After'] ?? 60);
    $backUrl = url()->previous() !== url()->current() ? url()->previous() : url('/');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="{{ $seconds }};url={{ $backUrl }}">
    <title>{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-white px-12 text-center">

    <div class="mb-12 animate-pop">
        <div class="h-32 w-32 rounded-full border-8 border-warning/20 border-t-warning animate-spin"></div>
    </div>

    <h1 class="text-6xl font-bold text-primary animate-rise-1">Mohon tunggu sebentar</h1>

    <p
        class="mt-6 max-w-3xl text-3xl font-medium leading-relaxed text-ink animate-rise-2"
        x-data="{ remaining: {{ $seconds }} }"
        x-init="setInterval(() => remaining > 0 && remaining--, 1000)"
    >
        Terlalu banyak permintaan dalam waktu singkat.<br>
        Layar akan kembali otomatis dalam
        <span class="font-semibold tabular-nums" x-text="remaining">{{ $seconds }}</span>
        detik.
    </p>


    <div class="absolute inset-x-0 bottom-0">
        <div class="h-3 bg-warning/15">
            <div
                class="h-full origin-left bg-warning animate-countdown"
                style="animation-duration: {{ $seconds }}s"
            ></div>
        </div>
    </div>

</body>
</html>
