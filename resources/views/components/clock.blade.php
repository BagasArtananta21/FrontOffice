@props(['date' => false])

<div
    x-data="{
        time: '',
        day: '',
        timer: null,
        tick() {
            const now = new Date();
            const timeZone = @js(config('app.timezone'));

            this.time = now.toLocaleTimeString('id-ID', { hour12: false, timeZone });
            this.day = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone });
        },
        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        destroy() {
            clearInterval(this.timer);
        },
    }"
    {{ $attributes->class('flex items-center gap-2') }}
>
    @isset($icon)
        {{ $icon }}
    @endisset

    <div class="leading-tight">
        <p class="tabular-nums">
            <span x-text="time">{{ now()->format('H.i.s') }}</span>
            {{ now()->format('T') }}
        </p>

        @if ($date)
            <p class="text-xs font-normal opacity-70" x-text="day">{{ now()->translatedFormat('l, d F Y') }}</p>
        @endif
    </div>
</div>
