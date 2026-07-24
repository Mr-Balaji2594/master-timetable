import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true })
        const page = pages[`./Pages/${name}.jsx`]
        if (!page) {
            throw new Error(`Page not found: ${name}`)
        }
        return page
    },
    setup({ el, App, props }) {
        const root = createRoot(el)
        root.render(<App {...props} />)
    },
    progress: {
        color: '#667eea',
    },
})
