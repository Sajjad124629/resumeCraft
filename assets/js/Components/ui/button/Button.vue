<script setup lang="ts">
import { cn } from '@/lib/utils'
import { HtmlHTMLAttributes } from 'vue';
const props = defineProps<{
    type?: "button" | "submit" | "reset"
    disabled?: boolean
    class?: HtmlHTMLAttributes['class']
}>()
</script>

<template>
    <button :type="props.type" :disabled="props.disabled" :class="cn(props.class)">
        <slot />
    </button>
</template>
