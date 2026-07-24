import Swal from 'sweetalert2'

export const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.onmouseenter = Swal.stopTimer
        toast.onmouseleave = Swal.resumeTimer
    }
})

export function showSuccess(message) {
    toast.fire({ icon: 'success', title: message })
}

export function showError(message) {
    toast.fire({ icon: 'error', title: message })
}

export function showWarning(message) {
    toast.fire({ icon: 'warning', title: message })
}

export function showConfirm(title, text) {
    return Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    })
}

export function showConfirmCustom({ title, text, icon = 'warning', confirmText = 'Yes', confirmColor = '#dc3545' }) {
    return Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel'
    })
}
