import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
export function userAccess() {
  const page = usePage()
  const access = computed(() => page.props.auth?.user?.designation?.access?.same || {})
  const can = (module:string, action:string) => {
    return !!access.value?.[module]?.[action]
  }
  return { can }
}
