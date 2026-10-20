@props([
    'code',
    'title',
    'message',
    'causes' => [],
])

@php
    $isDisplay = false;
    $user = null;
    $backUrl = null;
    $homeUrl = url('/');

    try {
        $isDisplay = \App\Models\DisplayDevice::findActiveByToken(
            request()->cookie(\App\Models\DisplayDevice::COOKIE)
        ) !== null;

        $user = auth()->user();

        $homeUrl = $isDisplay
            ? url('/display')
            : match ($user?->role) {
                'super_admin' => url('/super-admin'),
                'admin_fo' => url('/admin'),
                default => url('/'),
            };

        $previous = url()->previous();

        if (! $isDisplay && ! in_array($previous, [url()->current(), url('/')], true)) {
            $backUrl = $previous;
        }
    } catch (\Throwable) {
        //
    }

    $homeLabel = $user && ! $isDisplay ? 'Kembali ke Dashboard' : 'Kembali ke Beranda';
    $requestedUri = request()->getRequestUri();
    $now = now();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="h-dvh overflow-hidden bg-page">
    <div class="grid h-full lg:grid-cols-12">

        <main @class([
            'flex flex-col justify-between gap-6 px-6 py-8 lg:px-16 lg:py-12',
            'items-center text-center lg:col-span-12' => $isDisplay,
            'lg:col-span-7' => ! $isDisplay,
        ])>
            <div class="flex items-center gap-3">
                <img
                    src="{{ asset('images/logo.jpeg') }}"
                    alt="Logo {{ config('app.name') }}"
                    class="size-14 rounded-full border border-slate-200 bg-white p-1 object-contain"
                >
                <span class="text-xl font-bold text-primary">{{ config('app.name') }}</span>
            </div>

            <div class="mb-40">
                <p aria-hidden="true" class="text-[clamp(5rem,min(12vw,20vh),10rem)] font-bold leading-[0.85] tracking-tighter text-secondary">
                    {{ $code }}
                </p>

                <h1 class="mt-5 text-4xl font-bold tracking-tight text-primary sm:text-5xl">
                    {{ $title }}
                </h1>

                <p @class([
                    'mt-4 max-w-lg text-lg leading-relaxed text-ink',
                    'mx-auto' => $isDisplay,
                ])>
                    {{ $message }}
                </p>

                <div @class([
                    'mt-8 flex flex-col gap-3 sm:flex-row',
                    'sm:justify-center' => $isDisplay,
                ])>
                    <a
                        href="{{ $homeUrl }}"
                        @class([
                            'inline-flex items-center justify-center gap-2 rounded-md bg-primary px-6 font-semibold text-white transition-colors duration-150 hover:bg-tertiary',
                            'h-14 text-lg' => $isDisplay,
                            'h-12 text-base' => ! $isDisplay,
                        ])
                    >
                        {{ $homeLabel }}
                        <img src="{{ asset('images/icons/arrow-right.svg') }}" alt="" class="size-4">
                    </a>

                    @if ($backUrl)
                        <a
                            href="{{ $backUrl }}"
                            class="inline-flex h-12 items-center justify-center rounded-md bg-primary/10 px-6 text-base font-semibold text-primary transition-colors duration-150 hover:bg-primary/15"
                        >
                            Halaman Sebelumnya
                        </a>
                    @endif
                </div>
            </div>
        </main>

        @unless ($isDisplay)
            <aside class="hidden items-center bg-primary px-14 py-12 lg:col-span-5 lg:flex">
                <div class="w-full max-w-md">
                    <h2 class="text-xl font-bold text-white">Detail permintaan</h2>

                    <dl class="mt-6 space-y-5">
                        <div>
                            <dt class="text-sm font-medium text-white/70">Alamat</dt>
                            <dd class="mt-1 break-all text-lg font-semibold text-secondary">{{ $requestedUri }}</dd>
                        </div>

                        <div class="grid grid-cols-2 gap-5">
                            <div>
                                <dt class="text-sm font-medium text-white/70">Tanggal</dt>
                                <dd class="mt-1 text-lg font-semibold text-white">{{ $now->format('d/m/Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-white/70">Jam</dt>
                                <dd class="mt-1 text-lg font-semibold tabular-nums text-white">{{ $now->format('H:i:s') }} WITA</dd>
                            </div>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-white/70">Akun</dt>
                            <dd class="mt-1 text-lg font-semibold text-white">{{ $user?->name ?? 'Belum masuk' }}</dd>
                        </div>
                    </dl>

                    @if (count($causes) > 0)
                        <h2 class="mt-10 text-xl font-bold text-white">Kemungkinan penyebab</h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-base leading-relaxed text-white">
                            @foreach ($causes as $cause)
                                <li>{{ $cause }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <p class="mt-8 text-sm leading-relaxed text-white/70">
                        Jika masalah berlanjut, laporkan alamat, tanggal, dan jam di atas kepada pengembang.
                    </p>
                </div>
            </aside>
        @endunless
    </div>
</body>
</html>
