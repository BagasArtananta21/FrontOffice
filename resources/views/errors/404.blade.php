<x-layout-error
    code="404"
    title="Halaman tidak ditemukan"
    message="Alamat yang Anda buka tidak tersedia di sistem ini. Periksa kembali penulisannya, atau kembali ke halaman utama untuk melanjutkan pekerjaan."
    :causes="[
        'Alamat salah ketik.',
        'Halaman sudah dipindahkan atau dihapus.',
        'Datanya tidak tersedia untuk akun Anda.',
    ]"
/>
