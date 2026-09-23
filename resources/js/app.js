import Alpine from "alpinejs";

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
Alpine.start();


