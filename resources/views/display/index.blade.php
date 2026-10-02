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
    x-data="displayScreen('{{ route('display.status') }}', @js($initialShowForm || $errors->any()))"
    x-init="start()"
>
    {{-- Layar idle --}}
   <section x-show="! showForm" x-cloak class="fixed inset-0 bg-black">
        <video
            src="{{ asset('videos/profil.mp4') }}"
            x-effect="showForm ? $el.pause() : $el.play().catch(() => {})"
            autoplay
            muted
            loop
            playsinline
            class="h-full w-full object-cover"
            >
        </video>
    </section>

    {{-- Form tamu --}}
    <section x-show="showForm" x-cloak class="flex h-screen flex-col overflow-hidden bg-page px-10 py-8">
        <div class="mx-auto w-full max-w-7xl">
            <h1 class="text-3xl font-semibold text-primary">Formulir Buku Tamu - {{ $opd->kode_opd }}</h1>
            <p class="mt-2 text-xl text-ink">Kolom bertanda <span class="text-danger">*</span> wajib diisi.</p>

            @error('form')
                <div role="alert" class="mt-4 rounded-md bg-danger/10 px-5 py-4 text-xl font-medium text-danger">{{ $message }}</div>
            @enderror

            <form x-ref="guestForm" @submit="submitting = true" method="POST" action="{{ route('display.kunjungan.store') }}" class="mt-6 grid grid-cols-2 content-start gap-x-10 gap-y-6">
                @csrf

                {{-- Kolom kiri: identitas tamu --}}
                <div class="space-y-5">
                    <x-display.field name="nama_tamu" label="Nama" required>
                        <input id="nama_tamu" name="nama_tamu" type="text" value="{{ old('nama_tamu') }}" autocomplete="off" required class="display-input">
                    </x-display.field>

                    <x-display.field name="jenis_kelamin" label="Jenis kelamin" required>
                        <div class="grid grid-cols-2 gap-4">
                            @foreach (\App\Models\Kunjungan::JENIS_KELAMIN as $value => $label)
                                <label class="flex h-16 cursor-pointer items-center justify-center rounded-md border border-inactive bg-white text-xl font-medium text-ink has-checked:border-primary has-checked:bg-primary has-checked:text-white has-focus-visible:ring-2 has-focus-visible:ring-tertiary">
                                    <input
                                        type="radio"
                                        name="jenis_kelamin"
                                        value="{{ $value }}"
                                        @checked(old('jenis_kelamin') === $value)
                                        required
                                        class="sr-only"
                                    >
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </x-display.field>

                    <x-display.field name="no_hp" label="Nomor HP">
                        <input id="no_hp" name="no_hp" type="tel" inputmode="numeric" value="{{ old('no_hp') }}" autocomplete="off" class="display-input">
                    </x-display.field>

                    <x-display.field name="instansi_asal" label="Instansi asal">
                        <input id="instansi_asal" name="instansi_asal" type="text" value="{{ old('instansi_asal') }}" autocomplete="off" class="display-input">
                    </x-display.field>

                    <x-display.field name="alamat" label="Alamat">
                        <input id="alamat" name="alamat" type="text" value="{{ old('alamat') }}" maxlength="255" autocomplete="off" class="display-input">
                    </x-display.field>
                </div>

                {{-- Kolom kanan: tujuan kunjungan --}}
                <div class="space-y-5">
                    <div
                        x-data="{
                            bidang: @js(old('bidang_id')),
                            pegawai: @js($pegawai),
                            terpilih: @js(old('pegawai_id')),
                        }"
                        class="space-y-5"
                    >
                        <x-display.field name="bidang_id" label="Bidang yang dituju">
                            <select id="bidang_id" name="bidang_id" x-model="bidang" class="display-input">
                                <option value="">Belum tahu</option>
                                @foreach ($bidang as $item)
                                    <option value="{{ $item->id }}">{{ $item->nama_bidang }}</option>
                                @endforeach
                            </select>
                        </x-display.field>

                        <x-display.field name="pegawai_id" label="Pegawai yang dituju">
                            <select id="pegawai_id" name="pegawai_id" class="display-input">
                                <option value="">Belum tahu</option>
                                <template x-for="orang in pegawai.filter(p => ! bidang || ! p.bidang_id || p.bidang_id === bidang)" :key="orang.id">
                                    <option :value="orang.id" :selected="orang.id === terpilih" x-text="orang.nama_pegawai"></option>
                                </template>
                            </select>
                        </x-display.field>
                    </div>

                    <x-display.field name="keperluan" label="Keperluan" required>
                        <textarea id="keperluan" name="keperluan" rows="2" maxlength="1000" required class="display-input">{{ old('keperluan') }}</textarea>
                    </x-display.field>

                    <x-display.field name="tanda_tangan" label="Tanda tangan" required>
                        <div x-data="signaturePad(@js(old('tanda_tangan')))" class="relative">
                            <canvas x-ref="canvas" class="h-48 w-full touch-none rounded-md border border-inactive bg-white"></canvas>

                            <button type="button" @click="clear()"
                                class="absolute right-3 top-3 rounded-md bg-page px-5 py-3 text-lg font-medium text-ink">
                                Ulangi
                            </button>

                            <input type="hidden" name="tanda_tangan" :value="value">
                        </div>
                    </x-display.field>

                </div>

                <div class="col-span-2">
                    <button type="submit" :disabled="submitting" class="h-16 w-full rounded-md bg-primary text-2xl font-semibold text-white hover:bg-tertiary disabled:opacity-60" x-text="submitting ? 'Mengirim...' : 'Kirim'">
                        Kirim
                    </button>
                </div>
            </form>
        </div>
    </section>


    {{-- Lost Connection --}}
    <p x-show="offline" x-cloak class="fixed bottom-6 right-6 rounded-md bg-danger px-5 py-3 text-xl font-medium text-white">
        Tidak terhubung ke server
    </p>
</body>
</html>
