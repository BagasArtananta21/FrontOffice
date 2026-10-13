<p
    x-data="{
        time: '',
        timer: null,
        tick() {
            this.time = new Date().toLocaleTimeString('id-ID', { hour12: false, timeZone: @js(config('app.timezone')) });
        },
        init() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        destroy() {
            clearInterval(this.timer);
        },
    }"
    {{ $attributes }}
>
    <span x-text="time">{{ now()->format('H.i.s') }}</span>
    {{ now()->format('T') }}
</p>
