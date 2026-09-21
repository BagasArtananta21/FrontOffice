<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="min-h-screen"
    x-data="displayScreen('{{ route('display.status') }}')"
    x-init="start()"
>
    {{-- Layar idle --}}
    <section x-show="! showForm" x-cloak class="flex min-h-screen flex-col items-center justify-center bg-primary p-12">
        <img src="{{ asset('images/logo.jpeg') }}" alt="Logo instansi" class="mb-10 h-40 w-40 rounded-full bg-white/10">
        <h1 class="text-center text-5xl font-semibold text-white">{{ config('app.name') }}</h1>
        <p class="mt-6 text-center text-2xl text-white">Silakan menunggu, petugas akan menampilkan formulir tamu.</p>
    </section>

    {{-- Form tamu (placeholder, menunggu hasil wawancara) --}}
    <section x-show="showForm" x-cloak class="flex min-h-screen flex-col items-center justify-center bg-page p-12">
        <h1 class="text-center text-4xl font-semibold text-primary">Formulir Buku Tamu</h1>
        <p class="mt-6 text-center text-2xl text-ink">Isian formulir menyusul.</p>
    </section>

    {{-- Penanda kalau server tidak terjangkau --}}
    <p x-show="offline" x-cloak class="fixed bottom-6 right-6 rounded-md bg-danger px-5 py-3 text-xl font-medium text-white">
        Tidak terhubung ke server
    </p>
</body>
</html>
