<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="5;url={{ route('display.show') }}">
    <title>{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-white px-12 text-center">

    <div class="relative mb-14 flex h-44 w-44 items-center justify-center">
        <span class="absolute inset-0 rounded-full bg-success/20 animate-ring"></span>

        <div class="relative flex h-44 w-44 items-center justify-center rounded-full border-[6px] border-success/25 bg-white animate-pop">
            <svg viewBox="0 0 52 52" class="h-24 w-24 text-success" aria-hidden="true">
                <path
                    d="M14 27 l8 8 l16 -16"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    pathLength="100"
                    stroke-dasharray="100"
                    class="animate-draw"
                />
            </svg>
        </div>
    </div>

    <h1 class="text-6xl font-bold text-primary animate-rise-1">Terima kasih</h1>

    <p class="mt-6 max-w-3xl text-3xl font-medium leading-relaxed text-ink animate-rise-2">
        Data kunjungan Anda sudah kami terima.<br>
        Silakan menunggu, petugas akan segera membantu.
    </p>

    <div class="absolute inset-x-0 bottom-0">
        <p class="mb-5 text-2xl font-semibold text-ink">Layar akan kembali otomatis</p>
        <div class="h-3 bg-success/15">
            <div class="h-full origin-left bg-success animate-countdown"></div>
        </div>
    </div>

</body>
</html>
