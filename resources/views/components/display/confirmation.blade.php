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
<body class="flex min-h-screen flex-col items-center justify-center bg-primary p-12 text-center">
    <img src="{{ asset('images/icons/check.svg') }}" alt="Ikon berhasil" class="mb-10 h-32 w-32 rounded-full bg-white/10">
    <h1 class="text-5xl font-semibold text-white">Terima kasih</h1>
    <p class="mt-6 max-w-2xl text-2xl text-white">
        Data kunjungan Anda sudah kami terima. Silakan menunggu, petugas akan segera membantu.
    </p>
</body>
</html>
