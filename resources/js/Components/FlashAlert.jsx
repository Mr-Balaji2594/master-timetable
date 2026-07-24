import { useEffect } from 'react'
import { showSuccess, showError } from '../Helpers/sweetAlert'

export default function FlashAlert({ message, variant = 'success' }) {
    useEffect(() => {
        if (!message) return
        if (variant === 'danger' || variant === 'error') {
            showError(message)
        } else {
            showSuccess(message)
        }
    }, [message, variant])

    return null
}
