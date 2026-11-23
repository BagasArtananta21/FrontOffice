<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pasangkan Display — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.jpeg') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-screen flex-col">

    <header class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-8 py-4">
        <div class="text-primary">
            @include('filament.brand')
        </div>

        <x-clock class="text-base font-medium tabular-nums text-ink"/>

    </header>

    <main class="relative flex flex-1 items-center justify-center px-6 py-10">
        <div class="w-full max-w-4xl rounded-xl border border-slate-200 bg-white p-10 shadow-[0_8px_24px_rgba(15,44,89,0.08)]">
            <h1 class="text-center text-3xl font-semibold text-primary">Pasangkan Display Tamu</h1>
            <p class="mx-auto mt-3 max-w-md text-center text-lg text-ink">
                Masukkan PIN display yang tampil di panel admin untuk menghubungkan layar ini ke buku tamu.
            </p>

            <form method="POST" action="{{ route('display.pair.store') }}" class="mt-8">
                @csrf

                <label for="pin" class="mb-2 block text-sm font-semibold uppercase tracking-wide text-primary">
                    PIN Display
                </label>

                <div class="relative">
                    <img src="{{ asset('images/icons/key.svg') }}" alt="" class="pointer-events-none absolute left-5 top-1/2 size-6 -translate-y-1/2">
                    <input
                        id="pin"
                        type="text"
                        name="pin"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="off"
                        autofocus
                        required
                        @error('pin') aria-invalid="true" aria-describedby="pin-error" @enderror
                        @class([
                            'h-14 w-full rounded-md border bg-white pl-14 pr-5 text-2xl tracking-[0.5em] tabular-nums text-ink focus:border-tertiary focus:outline-none',
                            'border-inactive' => ! $errors->has('pin'),
                            'border-danger' => $errors->has('pin'),
                        ])
                    >
                </div>

                @error('pin')
                    <p id="pin-error" class="mt-2 text-base font-medium text-danger">{{ $message }}</p>
                @enderror


                <button
                    type="submit"
                    class="mt-6 h-14 w-full rounded-md bg-primary text-xl font-semibold text-white transition-colors duration-150 hover:bg-tertiary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tertiary/40 focus-visible:ring-offset-2"
                >
                    Pasangkan Perangkat
                </button>
            </form>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-ink">Petunjuk Singkat</h2>

                <ol class="mt-4 grid grid-cols-3 gap-4">
                    @foreach ([
                        'Buka panel admin, lihat PIN di bagian Status Display',
                        'Ketik 6 angka PIN di kolom di atas',
                        'Tekan Pasangkan Perangkat',
                    ] as $title)
                        <li class="flex gap-3 rounded-md border border-slate-200 bg-page p-4">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-semibold text-white">{{ $loop->iteration }}</span>
                            <p class="text-base font-semibold text-primary">{{ $title }}</p>
                        </li>
                    @endforeach
                </ol>

                <p class="mt-4 text-sm text-ink">
                    PIN cukup dimasukkan sekali. Setelah itu, layar ini dikenali otomatis.
                </p>
            </div>
        </div>
    </main>
</body>
</html>
