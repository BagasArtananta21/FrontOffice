import Alpine from "alpinejs";

window.Alpine = Alpine;
Alpine.data('displayScreen', (statusUrl) => ({
    showForm: false,
    offline: false,

    start(){
        this.poll();
        setInterval(() => this.poll(), 3000);
    },

    async poll(){
        try {
            const response = await fetch(statusUrl, {headers: {Accept: 'application/json'}});

            if (!response.ok){
                throw new Error(`HTTP ${response.status}`);
            }
            const data = await response.json();

            this.showForm = data.tampilkan_form;
            this.offline = false;
        } catch {
            this.offline = true;
        }
    },
}))
Alpine.start();


