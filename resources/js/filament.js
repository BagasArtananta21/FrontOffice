import Swal from 'sweetalert2';

const ICON_COLORS = {
    success: '#16A34A',
    error: '#DC2626',
    warning: '#F59E0B',
    info: '#1E3A8A',
};

document.addEventListener('livewire:init', () => {
    Livewire.on('swal', (event) => {
        const payload = Array.isArray(event) ? event[0] : event;
        const icon = payload.icon ?? 'success';
        const timer = payload.timer === null ? undefined : payload.timer ?? 2500;
        
        Swal.fire({
            icon,
            iconColor: ICON_COLORS[icon],
            title: payload.title,
            text: payload.text ?? '',
            timer,
            timerProgressBar: timer !== undefined,
            showConfirmButton: timer === undefined,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#0F2C59',
            customClass: {
                popup: `swal-popup swal-popup--${icon}`,
                title: 'swal-title',
                htmlContainer: 'swal-text',
                timerProgressBar: 'swal-progress',
            },
        });
    });
});
