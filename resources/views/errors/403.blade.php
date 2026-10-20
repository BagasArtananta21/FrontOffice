@php
    $reason = $exception->getMessage();

    $message = filled($reason) && $reason !== 'This action is unauthorized.'
        ? $reason
        : 'Anda tidak memiliki izin untuk membuka halaman ini.';
@endphp
<x-layout-error
    code="403"
    title="Akses ditolak"
    :message="$message"
    :causes="[
        'Akun Anda tidak memiliki izin untuk halaman ini.',
        'Anda masuk dengan akun yang berbeda dari yang seharusnya.',
        'Perangkat ini belum didaftarkan sebagai layar display.',
    ]"
/>
