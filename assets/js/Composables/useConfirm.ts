import Swal from 'sweetalert2'
export function useConfirm() {
    const confirmAction = async (options: {
        title?: string,
        text?: string,
        confirmButtonText?: string,
        icon?: 'warning' | 'info' | 'success' | 'error' | 'question'
    } = {}, onConfirm?: () => void) => {
        await Swal.fire({
            icon: options.icon || 'warning',
            title: options.title || 'Are you sure?',
            text: options.text || "You won't be able to revert this!",
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'Yes',
            cancelButtonText: 'Cancel',
            padding: '2em',
            customClass: { popup: 'sweet-alerts' },
        }).then((result: any) => {
            if (result.value) {
                if (onConfirm) onConfirm();
            }
        });

        // if (result.isConfirmed && onConfirm) {
        //     onConfirm();
        // }
    };

    return { confirmAction };
}

