import type { App } from 'vue'
import { usePage } from '@inertiajs/vue3'

function __(key: string, replace: Record<string, string> = {}): string {
    const page: any = usePage()
    let translation = page?.props?.translations?.[key] ?? page?.props?.language?.[key] ?? (typeof window !== 'undefined' && (window as any).__ ? (window as any).__(key, replace) : key)

    if (replace && typeof replace === 'object') {
        Object.keys(replace).forEach((rKey) => {
            translation = translation.replace(`:${rKey}`, replace[rKey])
        })
    }

    return translation
}

export default {
    install(app: App) {
        app.config.globalProperties.__ = __
    },
}

export { __ }

