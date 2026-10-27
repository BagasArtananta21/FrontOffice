@props(['clearButton' => true])

<div {{ $attributes->class('relative min-h-0') }}>
    <canvas x-ref="canvas" class="absolute inset-0 h-full w-full touch-none rounded-md border border-inactive bg-white"></canvas>

    <div class="pointer-events-none absolute inset-x-8 bottom-5">
        <div class="border-b-2 border-dashed border-inactive"></div>
        <p class="mt-2 text-lg text-ink">Tanda tangan di atas garis</p>
    </div>

    @if ($clearButton)
        <button type="button" x-show="value" x-cloak @click="clear()"
            class="absolute right-3 top-3 h-14 rounded-md bg-page px-6 text-lg font-medium text-ink transition-colors duration-150 hover:bg-slate-200">
            Ulangi
        </button>
    @endif

    <input type="hidden" name="tanda_tangan" :value="value">
</div>
