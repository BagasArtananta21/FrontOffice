<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — {{ config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative min-h-screen">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-96 bg-[radial-gradient(ellipse_at_top,#E0E7FF_0%,transparent_70%)]"></div>

    <main class="relative flex min-h-screen flex-col items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-[0_8px_24px_rgba(15,44,89,0.08)]">

            <div class="text-center">
                <img src="{{ asset('images/logo.jpeg') }}" alt="Logo {{ config('app.name') }}" class="mx-auto size-20">
                <h1 class="mt-3 text-2xl font-semibold text-primary">{{ config('app.name') }}</h1>
                <p class="mt-1 text-sm text-ink">Selamat datang! Silakan login.</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-primary">Email</label>
                    <div class="relative">
                        <img src="{{ asset('images/icons/mail.svg') }}" alt="" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="nama@email.com"
                            autocomplete="email"
                            required
                            autofocus
                            @class([
                                'h-11 w-full rounded-md bg-blue-50 pl-12 pr-12 text-sm text-primary placeholder:text-slate-400',
                                'outline-none transition duration-150 focus:bg-white focus:ring-2 focus:ring-tertiary/40',
                                'ring-2 ring-danger/60' => $errors->has('email'),
                            ])
                        >
                    </div>
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-primary">Kata Sandi</label>
                    <div class="relative" x-data="{ show: false }">
                        <img src="{{ asset('images/icons/password.svg') }}" alt="" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2">
                        <input
                            id="password"
                            type="password"
                            :type="show ? 'text' : 'password'"
                            name="password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                            required
                            @class([
                                'h-11 w-full rounded-md bg-blue-50 pl-12 pr-12 text-sm text-primary placeholder:text-slate-400',
                                'outline-none transition duration-150 focus:bg-white focus:ring-2 focus:ring-tertiary/40',
                                'ring-2 ring-danger/60' => $errors->has('password'),
                            ])
                        >
                        <button
                            type="button"
                            @click="show = !show"
                            :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded p-1 transition-colors duration-150 hover:bg-blue-100"
                    >
                            <img x-show="!show" src="{{ asset('images/icons/eye.svg') }}" alt="" class="size-5">
                            <img x-show="show" x-cloak src="{{ asset('images/icons/eye-off.svg') }}" alt="" class="size-5">
                        </button>
                    </div>
                </div>

                <button
                    type="submit"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-md bg-primary text-sm font-semibold text-white transition-colors duration-150 hover:bg-tertiary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-tertiary/40 focus-visible:ring-offset-2"
                >
                    Masuk ke Akun
                    <img src="{{ asset('images/icons/arrow-right.svg') }}" alt="" class="size-4">
                </button>
                @if ($errors->any())
                    <div role="alert" class="rounded-md bg-danger/5 px-3 py-2 text-center">
                        @foreach ($errors->all() as $message)
                            <p class="text-sm font-medium text-danger">{{ $message }}</p>
                        @endforeach
                    </div>
                @endif

            </form>

            <div class="my-5 flex items-center gap-3">
                <span class="h-px flex-1 bg-slate-200"></span>
                <span class="text-xs text-ink">atau</span>
                <span class="h-px flex-1 bg-slate-200"></span>
            </div>

            <a href="#" class="flex items-center gap-3 rounded-md bg-blue-50 p-3 transition-colors duration-150 hover:bg-blue-100">
                <img src="{{ asset('images/logo-dinas-buleleng.png') }}" alt="" class="size-6">
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-2">
                        <span class="text-sm font-semibold text-primary">Login dengan SANG Buleleng</span>
                        <span class="rounded bg-amber-200 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-primary">Resmi</span>
                    </span>
                    <span class="block text-xs text-ink">Identitas Digital ASN</span>
                </span>
                <img src="{{ asset('images/icons/key.svg') }}" alt="" class="size-5 shrink-0">
            </a>
        </div>

        <p class="mt-6 text-sm text-ink">
            Butuh bantuan?
            <a href="#" class="font-medium text-primary underline underline-offset-2 transition-colors duration-150 hover:text-tertiary">Hubungi Layanan Dukungan</a>
        </p>
    </main>
</body>
</html>
