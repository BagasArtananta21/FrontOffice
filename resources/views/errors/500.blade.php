<x-layout-error
    code="500"
    title="Terjadi kesalahan pada sistem"
    message="Permintaan Anda belum dapat diproses. Coba lagi beberapa saat lagi."
    :causes="[
        'Gangguan sementara pada server atau database.',
        'Ada data yang tidak dapat diproses oleh sistem.',
    ]"
/>
