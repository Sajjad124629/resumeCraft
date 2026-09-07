<script setup lang="ts">
import { cn } from '@/lib/utils'
import { Method } from '@inertiajs/core'
import { Link } from '@inertiajs/vue3'

defineOptions({
  inheritAttrs: false, // so we can control which attrs go where
})

const props = defineProps<{
  href: string | null
  tabindex?: number
  method?: Method
  as?: string
  class?: any
}>()
</script>

<template>
  <!-- Forward all events/extra attrs (like @click) to Inertia's Link -->
  <Link
    v-bind="$attrs"
    :href="props.href || ''"
    :tabindex="props.tabindex"
    :method="props.method"
    :as="props.as"
    :class="cn(props.class)"
  >
    <slot />
  </Link>
</template>
