<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pasangkan Display — {{ config('app.name') }}</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-primary p-8">
    <main class="w-full max-w-xl rounded-xl bg-white p-10">
        <img src="{{ asset('images/logo.jpeg') }}" alt="Logo instansi" class="mx-auto mb-8 h-20 w-20 rounded-full bg-page">

        <h1 class="text-center text-3xl font-semibold text-primary">Pasangkan Perangkat Display</h1>
        <p class="mt-3 text-center text-lg text-ink">Masukkan token perangkat dari administrator.</p>

        <form method="POST" action="{{ route('display.pair.store') }}" class="mt-8 space-y-6">
            @csrf

            <input
                type="text"
                name="token"
                value="{{ old('token') }}"
                autocomplete="off"
                spellcheck="false"
                autofocus
                required
                class="w-full rounded-md border border-inactive px-5 py-4 text-xl text-ink focus:border-tertiary focus:outline-none"
            >

            @error('token')
                <p class="text-center text-lg font-medium text-danger">{{ $message }}</p>
            @enderror

            <button type="submit" class="w-full rounded-md bg-primary py-4 text-xl font-semibold text-white hover:bg-tertiary">
                Pasangkan
            </button>
        </form>
    </main>
</body>
</html>
