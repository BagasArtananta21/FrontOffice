<section class="flex h-screen flex-col overflow-hidden bg-page px-10 py-8">
    <div class="mx-auto grid w-full max-w-7xl flex-1 grid-cols-2 gap-10">
        <div>
            <h1 class="text-3xl font-semibold text-primary">Periksa data Anda</h1>
            <p class="mt-2 text-xl text-ink">Data diisi oleh petugas. Data salah? Beri tahu petugas, data akan diperbarui di layar ini.</p>

            <dl class="mt-8 space-y-6">
                @foreach ($summary as $label => $value)
                    <div>
                        <dt class="text-lg font-medium text-ink">{{ $label }}</dt>
                        <dd class="mt-1 text-2xl font-semibold text-primary">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <form method="POST" action="{{ route('display.signature.store') }}" @submit="submitting = true" class="flex flex-col gap-6">
            @csrf

            @error('form')
                <div role="alert" class="rounded-md bg-danger/10 px-5 py-4 text-xl font-medium text-danger">{{ $message }}</div>
            @enderror

            <x-display.field name="tanda_tangan" label="Tanda tangan" required :for="false">
                <div x-data="signaturePad()" class="relative">
                    <canvas x-ref="canvas" class="h-80 w-full touch-none rounded-md border border-inactive bg-white"></canvas>

                    <button type="button" @click="clear()"
                        class="absolute right-3 top-3 rounded-md bg-page px-5 py-3 text-lg font-medium text-ink">
                        Ulangi
                    </button>

                    <input type="hidden" name="tanda_tangan" :value="value">
                </div>
            </x-display.field>

            <button type="submit" :disabled="submitting" class="h-16 w-full rounded-md bg-primary text-2xl font-semibold text-white hover:bg-tertiary disabled:opacity-60" x-text="submitting ? 'Mengirim...' : 'Kirim'">
                Kirim
            </button>
        </form>
    </div>
</section>
