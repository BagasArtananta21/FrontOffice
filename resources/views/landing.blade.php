<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} · Front Office Elektronik</title>
    <meta name="description" content="Buku tamu digital untuk lobi kantor. Tamu mengisi data diri dan tanda tangan di PC display, Admin FO melihat kunjungan dari panel.">
    <link rel="icon" href="{{ asset('images/logo.jpeg') }}">

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-dvh flex-col bg-white text-ink">

    @php
        $focusRing = 'focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-tertiary';

        // Kelas bersama untuk contoh form (ukuran lebih kecil dari display asli).
        $mockLabel = 'mb-1 block text-base font-medium text-primary';
        $mockInput = 'flex h-10 items-center rounded-md border border-inactive bg-white px-3 text-base text-ink';
    @endphp

    <header>
        <div class="mx-auto flex max-w-[1200px] items-center justify-between gap-4 px-6 py-3 lg:px-8 [@media(max-height:700px)]:py-2">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.jpeg') }}" alt="" width="44" height="44" class="size-11 shrink-0 rounded">
                <span class="text-2xl font-bold tracking-tight text-primary">{{ config('app.name') }}</span>
            </div>

            <a href="{{ route('login') }}"
               class="inline-flex h-11 items-center rounded-md border-2 border-primary px-5 text-lg font-semibold text-primary transition-colors duration-150 hover:bg-page active:translate-y-px {{ $focusRing }}">
                Masuk
            </a>
        </div>
    </header>

    <main class="flex flex-1 items-center">
        <section class="mx-auto grid w-full max-w-[1200px] items-start gap-10 px-6 py-8 lg:grid-cols-11 lg:gap-12 lg:px-8 [@media(max-height:700px)]:py-3">
            <div class="lg:col-span-5">
                <p class="text-base font-semibold text-primary">E-FO · Front Office Elektronik</p>

                <h1 class="mt-4 text-balance text-4xl font-bold leading-tight tracking-tight text-primary lg:text-5xl">
                    Buku tamu digital untuk lobi kantor
                </h1>
                <div aria-hidden="true" class="mt-5 h-1 w-14 rounded-full bg-secondary"></div>

                <p class="mt-6 text-xl leading-relaxed text-ink">
                    Tamu mengisi data diri dan tanda tangan di PC display, Admin FO melihat kunjungan dari panel.
                </p>

                <div class="mt-7 flex flex-col gap-4 sm:flex-row sm:flex-wrap">
                    <a href="{{ route('login') }}"
                       class="inline-flex h-14 w-full items-center justify-center rounded-md bg-primary px-6 text-lg font-semibold text-white transition-colors duration-150 hover:bg-tertiary active:translate-y-px sm:w-auto {{ $focusRing }}">
                        Masuk sebagai Admin
                    </a>

                    <a href="{{ route('display.pair') }}"
                       class="inline-flex h-14 w-full items-center justify-center rounded-md border-2 border-primary bg-white px-6 text-lg font-semibold text-primary transition-colors duration-150 hover:bg-page active:translate-y-px sm:w-auto {{ $focusRing }}">
                        Pasang PC display
                    </a>
                </div>

                <p class="mt-5 text-lg leading-relaxed text-ink">
                    Pasang PC display hanya di PC lobi, tempat tamu mengisi Formulir Tamu. Siapkan PIN 6 digit dari panel Admin FO.
                </p>
            </div>

            {{-- Contoh Formulir Tamu di PC display (statis, data fiktif) --}}
            <figure class="lg:col-span-6">
                <div data-ui aria-hidden="true" class="rounded-2xl bg-primary p-4">
                    <div class="rounded-xl bg-white p-5 [@media(max-height:700px)]:p-4">
                        <p class="text-xl font-semibold text-primary">Formulir Buku Tamu</p>
                        <p class="text-base text-ink">DISKOMINFOSANTI</p>

                        <div class="mt-4 grid gap-x-4 gap-y-3 sm:grid-cols-2">
                            <div>
                                <p class="{{ $mockLabel }}">Nama <span class="text-danger">*</span></p>
                                <div class="{{ $mockInput }}">Made Contoh</div>
                            </div>

                            <div>
                                <p class="{{ $mockLabel }}">Jenis kelamin <span class="text-danger">*</span></p>
                                <div class="grid grid-cols-2 gap-2">
                                    <span class="flex h-10 items-center justify-center rounded-md border border-primary bg-primary text-base font-medium text-white">Laki-laki</span>
                                    <span class="flex h-10 items-center justify-center rounded-md border border-inactive bg-white text-base font-medium text-ink">Perempuan</span>
                                </div>
                            </div>

                            <div>
                                <p class="{{ $mockLabel }}">Instansi asal</p>
                                <div class="{{ $mockInput }}">Dinas Contoh</div>
                            </div>

                            <div>
                                <p class="{{ $mockLabel }}">Keperluan <span class="text-danger">*</span></p>
                                <div class="{{ $mockInput }}">Konsultasi layanan</div>
                            </div>

                            <div class="sm:col-span-2">
                                <p class="{{ $mockLabel }}">Tanda tangan <span class="text-danger">*</span></p>
                                <div class="relative h-20 rounded-md [@media(max-height:700px)]:h-14 border border-inactive bg-white">
                                    <svg viewBox="0 0 240 70" class="absolute bottom-2 left-5 h-auto w-1/3" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                        <path class="text-primary" d="M6 52c10-22 18-40 26-38 9 2-8 40 2 40 8 0 16-34 24-34 7 0 1 28 9 28 9 0 13-20 22-20 8 0 4 18 12 18 10 0 20-16 34-20 16-4 34 4 52 2 14-2 26-8 34-14"/>
                                    </svg>
                                    <span class="absolute right-2 top-2 rounded-md bg-page px-3 py-1 text-base font-medium text-ink">Ulangi</span>
                                </div>
                            </div>

                            <div class="flex h-12 items-center justify-center rounded-md bg-primary text-lg font-semibold text-white sm:col-span-2">Kirim</div>
                        </div>
                    </div>
                </div>

                <figcaption class="mt-3 text-base text-ink">Contoh Formulir Tamu di PC display. Kolom bertanda <span class="text-danger">*</span> wajib diisi.</figcaption>
            </figure>
        </section>
    </main>

    <footer class="border-t border-ink/20">
        <p class="mx-auto max-w-[1200px] px-6 py-4 text-base leading-relaxed text-ink lg:px-8 [@media(max-height:700px)]:py-2">
            {{ config('app.name') }} · Front Office Elektronik · Dinas Komunikasi, Informatika, Persandian dan Statistik Kabupaten Buleleng
        </p>
    </footer>
</body>
</html>
