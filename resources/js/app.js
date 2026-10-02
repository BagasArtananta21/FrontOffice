import Alpine from "alpinejs";
import SignaturePad from "signature_pad";

window.Alpine = Alpine;
Alpine.data('displayScreen', (statusUrl, initialShowForm = false) => ({
    showForm: initialShowForm,
    offline: false,
    submitting: false,

    start(){
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

Alpine.start();


