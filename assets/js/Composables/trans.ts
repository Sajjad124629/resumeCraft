import type { App } from 'vue'
import { usePage } from '@inertiajs/vue3'

function __(key: string, replace: Record<string, string> = {}): string {
  const page: any = usePage()
  let translation = page.props.language?.[key] ?? key

  Object.keys(replace).forEach((rKey) => {
    translation = translation.replace(`:${rKey}`, replace[rKey])
  })

  return translation
}

export default {
  install(app: App) {
    app.config.globalProperties.__ = __
  },
}

export { __ }
