import Alpine from "alpinejs";
import SignaturePad from "signature_pad";
import Swal from "sweetalert2";

window.Alpine = Alpine;
Alpine.data('displayScreen', (statusUrl, initialShowForm = false, waitingKey = null, notice = null) => ({
    showForm: initialShowForm,
    offline: false,
    submitting: false,

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

        this.poll();
        setInterval(() => this.poll(), 3000);
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
    
            if ((data.waiting_key ?? null) !== waitingKey){
                window.location.reload();
                return;
            }
            if (this.showForm && !data.tampilkan_form){
                this.$refs.guestForm?.reset();
            }

            this.showForm = data.tampilkan_form;
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
        this.$watch('showForm', (visible) => visible && this.$nextTick(() => this.resize()));
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


