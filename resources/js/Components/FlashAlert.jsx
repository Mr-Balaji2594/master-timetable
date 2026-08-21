import { useEffect, useRef } from 'react'
import { showSuccess, showError } from '../Helpers/sweetAlert'

// `message` is the flash prop shared by the server, shaped as
// { message: string, id: string }. The unique `id` lets us detect every new
// flash even when two consecutive actions produce identical message text
// (e.g. editing two staff in a row both say "updated successfully"), while
// still suppressing duplicate renders of the same flash (React StrictMode).
export default function FlashAlert({ message, variant = 'success' }) {
    const shownRef = useRef(null)

    useEffect(() => {
        const text = message?.message ?? message
        const id = message?.id
        if (!text) return

        if (id) {
            if (shownRef.current === id) return
            shownRef.current = id
        } else {
            if (shownRef.current === text) return
            shownRef.current = text
        }

        if (variant === 'danger' || variant === 'error') {
            showError(text)
        } else {
            showSuccess(text)
        }
    }, [message, variant])

    return null
}
