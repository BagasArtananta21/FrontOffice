<x-layout-error
    code="419"
    title="Halaman kedaluwarsa"
    message="Sesi Anda telah kedaluwarsa. Silakan muat ulang halaman ini dan coba lagi."
    :causes="[
        'Halaman dibiarkan terbuka terlalu lama tanpa aktivitas.',
        'Anda keluar dari akun di tab atau perangkat lain.',
        'Cookie browser dihapus atau diblokir.',
    ]"
/>