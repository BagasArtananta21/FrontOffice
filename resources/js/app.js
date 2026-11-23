import Alpine from "alpinejs";
import SignaturePad from "signature_pad";
import Swal from "sweetalert2";

window.Alpine = Alpine;

const IDLE_SCREENSAVER_MS = 5 * 60 * 1000; 

Alpine.data('displayScreen', (statusUrl, waitingKey = null, notice = null, reloadKey = null) => ({
    screensaver: false,
    offline: false,
    submitting: false,
    idleTimer: null,

    start(){
        if (notice) {
            Swal.fire({
                icon: 'info',
                iconColor: '#1E3A8A',
                titleText: notice.title,
                text: notice.text,
                timer: 6000,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal-display',
                    title: 'swal-display-title',
                    htmlContainer: 'swal-display-text',
                    timerProgressBar: 'swal-display-progress',
                },
            });
        }

        this.watchActivity();
        this.poll();
        setInterval(() => this.poll(), 3000);
    },

    resetIdleTimer(){
        clearTimeout(this.idleTimer);
        this.idleTimer = setTimeout(() => { this.screensaver = true }, IDLE_SCREENSAVER_MS);
    },

    wake(){
        this.screensaver = false;
        this.resetIdleTimer();
    },

    watchActivity(){
        ['pointerdown', 'pointermove', 'keydown', 'wheel'].forEach((event) => {
            window.addEventListener(event, () => {
                if (!this.screensaver) {
                    this.resetIdleTimer();
                }
            }, {passive: true});
        });

        this.resetIdleTimer();
    },

    async poll(){
        if (this.submitting){
            return;
        }
        
        try {
            const response = await fetch(statusUrl, {headers: {Accept: 'application/json'}});

            if (response.status === 403) {
                window.location.reload();
                return;
            }

            if (!response.ok){
                throw new Error(`HTTP ${response.status}`);
            }
            
            const data = await response.json();

            if (data.csrf_token){
                this.$root.querySelectorAll('input[name="_token"]').forEach((input) => {
                    input.value = data.csrf_token;
                });
            }
    
            if ((data.waiting_key ?? null) !== waitingKey){
                window.location.reload();
                return;
            }

            if ((data.reload_key ?? null) !== reloadKey){
                window.location.reload();
                return;
            }
            
            this.offline = false;
        } catch {
            this.offline = true;
        }


    },
}))

Alpine.data('signaturePad', (initial = null) => ({
    pad: null,
    value: initial ?? '',

    init(){
        this.pad = new SignaturePad(this.$refs.canvas, {
            penColor: 'rgb(0, 0, 0)',
            backgroundColor: 'rgb(255, 255, 255)',
        });

        this.pad.addEventListener('endStroke', () => {
            this.value = this.pad.toDataURL('image/png');
        });

        this.$el.closest('form')?.addEventListener('reset', () => this.clear());
        window.addEventListener('resize', () => this.resize());
        this.$nextTick(() => this.resize());
    },

    resize(){
        const canvas = this.$refs.canvas;

        if (canvas.offsetWidth === 0) {
            return;
        }

        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);

        this.pad.clear();

        if (this.value) {
            this.pad.fromDataURL(this.value, {ratio});
        }
    },

    clear(){
        this.pad.clear();
        this.value = '';
    },

}))

Alpine.data('signatureCountdown', (seconds) => ({
    endAt: Date.now() + seconds * 1000,
    left: seconds,

    init() {
        setInterval(() => {
            this.left = Math.max(0, Math.ceil((this.endAt - Date.now()) / 1000));
        }, 500);
    },

    get label() {
        const minutes = String(Math.floor(this.left / 60)).padStart(2, '0');
        const secs = String(this.left % 60).padStart(2, '0');

        return `${minutes}:${secs}`;
    },
}))

Alpine.start();


