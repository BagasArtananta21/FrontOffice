@php
    $name = $summary['Nama'] ?? '';
    $details = \Illuminate\Support\Arr::except($summary, 'Nama');
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<section class="flex h-screen flex-col overflow-hidden bg-page px-10 py-20">
    <div class="mx-auto flex min-h-0 w-full max-w-7xl flex-1 flex-col">
        <header class="flex items-start justify-between gap-6 border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-3xl font-semibold text-primary">Konfirmasi Kunjungan Tamu - {{ $opd->kode_opd }}</h1>
                <p class="mt-2 text-xl text-ink">Periksa data Anda dengan teliti, lalu tanda tangan untuk menyelesaikan.</p>
            </div>

            @isset($signatureSecondsLeft)
                <div x-data="signatureCountdown({{ $signatureSecondsLeft }})" class="shrink-0 rounded-lg border border-slate-200 bg-white px-6 py-3 text-right">
                    <p class="text-lg font-medium text-ink">Batas waktu</p>
                    <p class="text-4xl font-semibold tabular-nums" :class="left <= 60 ? 'text-danger' : 'text-primary'" x-text="label">
                        {{ gmdate('i:s', $signatureSecondsLeft) }}
                    </p>
                </div>
            @endisset
        </header>

        <div class="grid min-h-0 flex-1 grid-cols-2 pt-6">
            <div class="flex min-h-0 flex-col pr-8">
                <h2 class="text-2xl font-semibold text-primary">Rincian data kunjungan</h2>

                <div class="mt-5 flex items-center gap-5 rounded-lg border border-slate-200 bg-white p-5">
                    <div class="flex size-16 shrink-0 items-center justify-center rounded-lg bg-primary text-2xl font-semibold text-white">{{ $initials }}</div>
                    <div class="min-w-0">
                        <p class="text-lg font-medium text-ink">Nama pengunjung</p>
                        <p class="line-clamp-2 text-4xl font-semibold text-primary">{{ $name }}</p>
                    </div>
                </div>

                <dl class="mt-2 min-h-0 flex-1 divide-y divide-slate-200 overflow-hidden">
                    @foreach ($details as $label => $value)
                        <div class="grid grid-cols-[13rem_1fr] items-baseline gap-4 py-3">
                            <dt class="text-lg font-medium text-ink">{{ $label }}</dt>
                            <dd @class([
                                'text-2xl font-semibold text-primary',
                                'line-clamp-4' => $label === 'Keperluan',
                                'line-clamp-2' => $label !== 'Keperluan',
                            ])>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="mt-6 flex items-start gap-4 rounded-lg bg-warning/10 p-5">
                    <img src="{{ asset('images/icons/alert-primary.svg') }}" alt="" class="size-8 shrink-0">
                    <p class="text-lg text-ink">
                        <span class="font-semibold text-primary">Nomor HP dan alamat tidak ditampilkan di layar ini.</span>
                        Jika ada data yang salah, beri tahu petugas. Perbaikan langsung tampil di layar ini.
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('display.signature.store') }}"
                x-data="signaturePad()"
                @submit="submitting = true"
                class="flex min-h-0 flex-col border-l border-slate-200 pl-8"
            >
                @csrf

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-semibold text-primary">Tanda tangan digital <span class="text-danger">*</span></h2>
                        <p class="mt-1 text-lg text-ink">Gunakan jari atau pena pada kotak di bawah.</p>
                    </div>

                    <button type="button" @click="clear()" :disabled="! value"
                        class="h-14 shrink-0 rounded-md border border-inactive bg-white px-6 text-lg font-medium text-ink transition-colors duration-150 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40">
                        Bersihkan
                    </button>
                </div>

                @error('form')
                    <div role="alert" class="mt-4 rounded-md bg-danger/10 px-5 py-4 text-xl font-medium text-danger">{{ $message }}</div>
                @enderror

                <x-display.signature-pad :clear-button="false" class="mt-5 flex-1" />

                @error('tanda_tangan')
                    <p class="mt-2 text-lg text-danger">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    :disabled="submitting || ! value"
                    x-text="submitting ? 'Mengirim...' : 'Kirim dan Selesaikan'"
                    class="mt-6 h-16 w-full shrink-0 rounded-md bg-primary text-2xl font-semibold text-white transition-colors duration-150 hover:bg-tertiary disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:bg-primary"
                >
                    Kirim dan Selesaikan
                </button>
            </form>
        </div>
    </div>
</section>
