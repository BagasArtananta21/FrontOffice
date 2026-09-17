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

        Swal.fire({
            icon,
            iconColor: ICON_COLORS[icon],
            title: payload.title,
            text: payload.text ?? '',
            timer: payload.timer ?? 2500,
            timerProgressBar: true,
            showConfirmButton: false,
            customClass: {
                popup: `swal-popup swal-popup--${icon}`,
                title: 'swal-title',
                htmlContainer: 'swal-text',
                timerProgressBar: 'swal-progress',
            },
        });
    });
});
