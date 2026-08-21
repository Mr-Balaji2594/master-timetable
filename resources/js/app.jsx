import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import TopLoader from './Components/TopLoader'

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx')
        const page = pages[`./Pages/${name}.jsx`]
        if (!page) {
            throw new Error(`Page not found: ${name}`)
        }
        return page()
    },
    setup({ el, App, props }) {
        const root = createRoot(el)
        root.render(
            <>
                <TopLoader />
                <App {...props} />
            </>
        )
    },
})
